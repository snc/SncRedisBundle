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


use Doctrine\Common\Cache\RedisCache;
use Snc\RedisBundle\Command\RedisBaseCommand;
use Snc\RedisBundle\DependencyInjection\Configuration\Configuration;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisEnvDsn;
use Snc\RedisBundle\Factory\PredisParametersFactory;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Predis\Command\Processor\KeyPrefixProcessor;
use Symfony\Component\HttpKernel\Kernel;

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

        if (isset($config['session'])) {
            $this->loadSession($config, $container, $loader);
        }

        if (isset($config['doctrine']) && count($config['doctrine'])) {
            $this->loadDoctrine($config, $container);
        }

        if (isset($config['monolog'])) {
            if (!empty($config['clients'][$config['monolog']['client']]['logging'])) {
                throw new InvalidConfigurationException(sprintf('You have to disable logging for the client "%s" that you have configured under "snc_redis.monolog.client"', $config['monolog']['client']));
            }
            $this->loadMonolog($config, $container);
        }

        if (isset($config['swiftmailer'])) {
            $this->loadSwiftMailer($config, $container);
        }

        if (isset($config['profiler_storage'])) {
            $this->loadProfilerStorage($config, $container, $loader);
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
            if (method_exists($container, 'resolveEnvPlaceholders')) {
                $container->resolveEnvPlaceholders($dsn, null, $usedEnvs);
            }

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

        if (method_exists($container, 'resolveEnvPlaceholders')) {
            $client['type'] = $container->resolveEnvPlaceholders($client['type'], true);
        }

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
        if (method_exists($container, 'resolveEnvPlaceholders')) {
            $profile = $container->resolveEnvPlaceholders($profile, true);
        }
        $profile = !is_string($profile) ? sprintf('%.1F', $profile) : $profile;
        $profileId = sprintf('snc_redis.client.%s_profile', $client['alias']);
        $profileDef = new Definition(get_class(\Predis\Profile\Factory::get($profile))); // TODO get_class alternative?
        $profileDef->setPublic(false);
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
        $optionDef->setPublic(false);
        $optionDef->addArgument($client['options']);
        $container->setDefinition($optionId, $optionDef);
        $clientDef = new Definition($container->getParameter('snc_redis.client.class'));
        $clientDef->setPublic(false);
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
        $parameterDef->setPublic(false);
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
        $phpredisDef->setPublic(false);

        // Older version of phpredis extension do not support lazy loading
        $minimumVersionForLazyLoading = '4.1.1';
        $phpRedisVersion = phpversion('redis');
        $supportsLazyServices = version_compare($phpRedisVersion, $minimumVersionForLazyLoading, '>=');
        $phpredisDef->setLazy($supportsLazyServices);
        if (!$supportsLazyServices) {
            @trigger_error(
                sprintf('Lazy loading Redis is not supported on PhpRedis %s. Please update to PhpRedis %s or higher.', $phpRedisVersion, $minimumVersionForLazyLoading),
                E_USER_WARNING
            );
        }

        $phpredisId = sprintf('snc_redis.%s', $client['alias']);
        $container->setDefinition($phpredisId, $phpredisDef);
    }

    /**
     * Loads the session configuration.
     *
     * @param array            $config    A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader    $loader    A XmlFileLoader instance
     */
    protected function loadSession(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('session.xml');

        $container->setParameter('snc_redis.session.client', $config['session']['client']);
        $container->setParameter('snc_redis.session.prefix', $config['session']['prefix']);
        $container->setParameter('snc_redis.session.locking', $config['session']['locking']);
        $container->setParameter('snc_redis.session.spin_lock_wait', $config['session']['spin_lock_wait']);

        $client = $container->getParameter('snc_redis.session.client');

        $client = sprintf('snc_redis.%s', $client);

        $container->setAlias('snc_redis.session.client', $client);

        if (isset($config['session']['ttl'])) {
            $definition = $container->getDefinition('snc_redis.session.handler');
            $definition->addMethodCall('setTtl', array($config['session']['ttl']));
        }
    }

    /**
     * Loads the Doctrine configuration.
     *
     * @param array            $config    A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDoctrine(array $config, ContainerBuilder $container)
    {
        foreach ($config['doctrine'] as $name => $cache) {
            if (empty($cache['entity_managers']) && empty($cache['document_managers'])) {
                throw new InvalidConfigurationException(sprintf('Enabling the doctrine %s section requires it to reference either an entity manager or document manager', $name));
            }

            if ('second_level_cache' === $name) {
                $name = 'second_level_cache.region_cache_driver';
            }

            $definitionFunction = function ($client, $cache) use ($container, $config): Definition {
                $cacheClassParam = 'snc_redis.doctrine_cache_' . $config['clients'][$cache['client']]['type'] . '.class';
                if (RedisAdapter::class === $container->getParameter($cacheClassParam)) {
                    return new Definition(RedisAdapter::class, [$client, $cache['namespace'] ?? '']);
                }

                $def = new Definition($container->getParameter($cacheClassParam), [$client]);
                if ($cache['namespace']) {
                    $def->addMethodCall('setNamespace', [$cache['namespace']]);
                }

                return $def;
            };

            $client = new Reference(sprintf('snc_redis.%s', $cache['client']));
            foreach ($cache['entity_managers'] as $em) {
                $id = sprintf('snc_redis.doctrine.orm.%s_%s', $em, $name);
                $def = call_user_func_array($definitionFunction, array($client, $cache));
                $container->setDefinition($id, $def);
                $container->setAlias(sprintf('doctrine.orm.%s_%s', $em, $name), $id);
            }
            foreach ($cache['document_managers'] as $dm) {
                $id = sprintf('snc_redis.doctrine_mongodb.odm.%s_%s', $dm, $name);
                $def = call_user_func_array($definitionFunction, array($client, $cache));
                $container->setDefinition($id, $def);
                $container->setAlias(sprintf('doctrine_mongodb.odm.%s_%s', $dm, $name), $id);
            }
        }
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

        $def->setPublic(false);
        if (!empty($config['monolog']['formatter'])) {
            $def->addMethodCall('setFormatter', array(new Reference($config['monolog']['formatter'])));
        }
        $container->setDefinition('snc_redis.monolog.handler', $def);
    }

    /**
     * Loads the SwiftMailer configuration.
     *
     * @param array            $config    A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadSwiftMailer(array $config, ContainerBuilder $container)
    {
        $def = new Definition($container->getParameter('snc_redis.swiftmailer_spool.class'));
        $def->setPublic(false);
        $def->addMethodCall('setRedis', array(new Reference(sprintf('snc_redis.%s', $config['swiftmailer']['client']))));
        $def->addMethodCall('setKey', array($config['swiftmailer']['key']));
        $container->setDefinition('snc_redis.swiftmailer.spool', $def);
        $container->setAlias('swiftmailer.spool.redis', 'snc_redis.swiftmailer.spool');
    }

     /* Loads the profiler storage configuration.
     *
     * @param array            $config    A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader    $loader    A XmlFileLoader instance
     */
    protected function loadProfilerStorage(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        if (Kernel::VERSION_ID >= 40400) {
            @trigger_error('Redis profiler storage is not available anymore since Symfony 4.4. The option has been disabled automatically.', E_USER_WARNING);

            return;
        }

        $loader->load('profiler_storage.xml');

        $container->setParameter('snc_redis.profiler_storage.client', $config['profiler_storage']['client']);
        $container->setParameter('snc_redis.profiler_storage.ttl', $config['profiler_storage']['ttl']);

        $client = $container->getParameter('snc_redis.profiler_storage.client');
        $client = sprintf('snc_redis.%s', $client);
        $container->setAlias('snc_redis.profiler_storage.client', $client);
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
