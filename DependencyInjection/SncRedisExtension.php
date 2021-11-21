<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DependencyInjection;


use Predis\Command\Processor\KeyPrefixProcessor;
use Snc\RedisBundle\Command\RedisBaseCommand;
use Snc\RedisBundle\DependencyInjection\Configuration\Configuration;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisEnvDsn;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Factory\PredisParametersFactory;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SncRedisExtension extends Extension
{
    /**
     * Loads the configuration.
     *
     * @param array            $configs   An array of configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws InvalidConfigurationException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('redis.xml');

        $mainConfig = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($mainConfig, $configs);

        foreach ($config['class'] as $name => $class) {
            $container->setParameter(sprintf('snc_redis.%s.class', $name), $class);
        }

        foreach ($config['clients'] as $client) {
            $this->loadClient($client, $container);
        }

        if (isset($config['monolog'])) {
            if (!empty($config['clients'][$config['monolog']['client']]['logging'])) {
                throw new InvalidConfigurationException(sprintf('You have to disable logging for the client "%s" that you have configured under "snc_redis.monolog.client"', $config['monolog']['client']));
            }
            $this->loadMonolog($config, $container);
        }

        $container->registerForAutoconfiguration(RedisBaseCommand::class)
            ->addTag('snc_redis.command');
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return 'http://symfony.com/schema/dic/redis';
    }

    /**
     * @return string
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    /**
     * Loads a redis client.
     *
     * @param array            $client    A client configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadClient(array $client, ContainerBuilder $container)
    {
        $dsnResolver = function ($dsn) use ($container) {
            $usedEnvs = null;
            $container->resolveEnvPlaceholders($dsn, null, $usedEnvs);

            if ($usedEnvs) {
                return new RedisEnvDsn($dsn);
            }

            $parsedDsn = new RedisDsn($dsn);

            if ($parsedDsn->isValid()) {
                return $parsedDsn;
            }

            throw new \InvalidArgumentException(sprintf('Given Redis DSN "%s" is invalid.', $dsn));
        };

        $client['dsns'] = array_map($dsnResolver, $client['dsns']);
        $client['type'] = $container->resolveEnvPlaceholders($client['type'], true);

        switch ($client['type']) {
            case 'predis':
                $this->loadPredisClient($client, $container);
                break;
            case 'phpredis':
                $this->loadPhpredisClient($client, $container);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('The redis client type %s is invalid.', $client['type']));
                break;
        }
    }

    /**
     * Loads a redis client using predis.
     *
     * @param array            $client    A client configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadPredisClient(array $client, ContainerBuilder $container)
    {
        if (null === $client['options']['cluster']) {
            unset($client['options']['cluster']);
        } else {
            unset($client['options']['replication']);
        }

        // predis connection parameters have been renamed in v0.8
        $client['options']['async_connect'] = $client['options']['connection_async'];
        $client['options']['timeout'] = $client['options']['connection_timeout'];
        $client['options']['persistent'] = $client['options']['connection_persistent'];
        $client['options']['exceptions'] = $client['options']['throw_errors'];
        unset($client['options']['connection_async']);
        unset($client['options']['connection_timeout']);
        unset($client['options']['connection_persistent']);
        unset($client['options']['throw_errors']);

        $connectionAliases = array();
        $connectionCount = count($client['dsns']);

        /** @var RedisDsn $dsn */
        foreach ($client['dsns'] as $i => $dsn) {
            $connectionAlias = $dsn instanceof RedisDsn ? $dsn->getAlias() : null;
            if (!$connectionAlias) {
                $connectionAlias = 1 === $connectionCount ? $client['alias'] : $client['alias'] . ($i + 1);
            }
            $connectionAliases[] = $connectionAlias;

            $connection = $client['options'];
            $connection['logging'] = $client['logging'];
            $connection['alias'] = $connectionAlias;

            $this->loadPredisConnectionParameters($client['alias'], $connection, $container, $dsn);
        }

        $profile = $client['options']['profile'];
        // TODO can be shared between clients?!
        $profile = $container->resolveEnvPlaceholders($profile, true);
        $profile = !is_string($profile) ? sprintf('%.1F', $profile) : $profile;
        $profileId = sprintf('snc_redis.client.%s_profile', $client['alias']);
        $profileDef = new Definition(get_class(\Predis\Profile\Factory::get($profile))); // TODO get_class alternative?
        if (null !== $client['options']['prefix']) {
            $processorId = sprintf('snc_redis.client.%s_processor', $client['alias']);
            $processorDef = new Definition(KeyPrefixProcessor::class);
            $processorDef->setArguments(array($client['options']['prefix']));
            $container->setDefinition($processorId, $processorDef);
            $profileDef->addMethodCall('setProcessor', array(new Reference($processorId)));
        }
        $container->setDefinition($profileId, $profileDef);
        $client['options']['profile'] = new Reference($profileId);

        $optionId = sprintf('snc_redis.client.%s_options', $client['alias']);
        $optionDef = new Definition($container->getParameter('snc_redis.client_options.class'));
        $optionDef->addArgument($client['options']);
        $container->setDefinition($optionId, $optionDef);
        $clientDef = new Definition($container->getParameter('snc_redis.client.class'));
        $clientDef->addTag('snc_redis.client', array('alias' => $client['alias']));
        if (1 === $connectionCount && !isset($client['options']['cluster']) && !isset($client['options']['replication'])) {
            $clientDef->addArgument(new Reference(sprintf('snc_redis.connection.%s_parameters.%s', $connectionAliases[0], $client['alias'])));
        } else {
            $connections = array();
            foreach ($connectionAliases as $alias) {
                $connections[] = new Reference(sprintf('snc_redis.connection.%s_parameters.%s', $alias, $client['alias']));
            }
            $clientDef->addArgument($connections);
        }

        $clientDef->addArgument(new Reference($optionId));
        $container->setDefinition(sprintf('snc_redis.%s', $client['alias']), $clientDef);
    }

    /**
     * Loads a connection.
     *
     * @param string               $clientAlias The client alias
     * @param array                $connection  A connection configuration
     * @param ContainerBuilder     $container   A ContainerBuilder instance
     * @param RedisEnvDsn|RedisDsn $dsn         DSN object
     */
    protected function loadPredisConnectionParameters($clientAlias, array $connection, ContainerBuilder $container, $dsn)
    {
        $parametersClass = $container->getParameter('snc_redis.connection_parameters.class');
        $parameterId = sprintf('snc_redis.connection.%s_parameters.%s', $connection['alias'], $clientAlias);

        $parameterDef = new Definition($parametersClass);
        $parameterDef->setFactory(array(PredisParametersFactory::class, 'create'));
        $parameterDef->addArgument($connection);
        $parameterDef->addArgument($parametersClass);
        $parameterDef->addArgument((string) $dsn);
        $parameterDef->addTag('snc_redis.connection_parameters', array('clientAlias' => $clientAlias));
        $container->setDefinition($parameterId, $parameterDef);
    }

    /**
     * Loads a redis client using phpredis.
     *
     * @param array            $client    A client configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \RuntimeException
     */
    protected function loadPhpredisClient(array $client, ContainerBuilder $container)
    {
        $connectionCount = count($client['dsns']);
        $hasClusterOption = null !== $client['options']['cluster'];

        if ($connectionCount > 1 && !$hasClusterOption) {
            throw new \LogicException(sprintf('\RedisArray is not supported yet but \RedisCluster is: set option "cluster" to true to enable it.'));
        }

        if ($hasClusterOption) {
            $phpredisClientClass =
                $client['logging']
                    ? $container->getParameter('snc_redis.phpredis_clusterclient_connection_wrapper.class')
                    : $container->getParameter('snc_redis.phpredis_clusterclient.class');
        } else {
            $phpredisClientClass =
                $client['logging']
                ? $container->getParameter('snc_redis.phpredis_connection_wrapper.class')
                : $container->getParameter('snc_redis.phpredis_client.class');
        }

        $phpredisDef = new Definition($phpredisClientClass);
        $factoryDefinition = new Definition(
            PhpredisClientFactory::class, [
                new Reference('snc_redis.logger'),
            ]
        );

        if ($container->getParameter('kernel.debug')) {
            $factoryDefinition->addArgument(new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE));
        }

        $phpredisDef->setFactory([$factoryDefinition, 'create']);
        $phpredisDef->addArgument($phpredisClientClass);
        $phpredisDef->addArgument(array_map('strval', $client['dsns']));
        $phpredisDef->addArgument($client['options']);
        $phpredisDef->addArgument($client['alias']);
        $phpredisDef->addTag('snc_redis.client', array('alias' => $client['alias']));
        $phpredisDef->setLazy(true);

        $container->setDefinition(sprintf('snc_redis.%s', $client['alias']), $phpredisDef);
    }

    /**
     * Loads the Monolog configuration.
     *
     * @param array            $config    A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadMonolog(array $config, ContainerBuilder $container)
    {
        $ref = new Reference(sprintf('snc_redis.%s', $config['monolog']['client']));

        $def = new Definition($container->getParameter('snc_redis.monolog_handler.class'), array(
            $ref,
            $config['monolog']['key']
        ));

        if (!empty($config['monolog']['formatter'])) {
            $def->addMethodCall('setFormatter', array(new Reference($config['monolog']['formatter'])));
        }
        $container->setDefinition('snc_redis.monolog.handler', $def);
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
