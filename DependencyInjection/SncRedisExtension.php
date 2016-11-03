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

use Snc\RedisBundle\DependencyInjection\Configuration\Configuration;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * SncRedisExtension
 */
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
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return 'http://symfony.com/schema/dic/redis';
    }

    /**
     * {@inheritdoc}
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
        switch ($client['type']) {
            case 'predis':
                $this->loadPredisClient($client, $container);
                break;
            case 'phpredis':
                $this->loadPhpredisClient($client, $container);
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
        foreach ($client['dsns'] as $i => $dsn) {
            /** @var \Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn $dsn */
            if (!$connectionAlias = $dsn->getAlias()) {
                $connectionAlias = 1 === $connectionCount ? $client['alias'] : $client['alias'] . ($i + 1);
            }
            $connectionAliases[] = $connectionAlias;
            $connection = $client['options'];
            $connection['logging'] = $client['logging'];
            $connection['alias'] = $connectionAlias;
            if (null !== $dsn->getSocket()) {
                $connection['scheme'] = 'unix';
                $connection['path'] = $dsn->getSocket();
            } else {
                $connection['scheme'] = 'tcp';
                $connection['host'] = $dsn->getHost();
                $connection['port'] = $dsn->getPort();
            }
            if (null !== $dsn->getDatabase()) {
                $connection['database'] = $dsn->getDatabase();
            }
            $connection['password'] = $dsn->getPassword();
            $connection['weight'] = $dsn->getWeight();
            $this->loadPredisConnectionParameters($client['alias'], $connection, $container);
        }

        // TODO can be shared between clients?!
        $profileId = sprintf('snc_redis.client.%s_profile', $client['alias']);
        $profileDef = new Definition(get_class(\Predis\Profile\Factory::get($client['options']['profile']))); // TODO get_class alternative?
        $profileDef->setPublic(false);
        if (null !== $client['options']['prefix']) {
            $processorId = sprintf('snc_redis.client.%s_processor', $client['alias']);
            $processorDef = new Definition('Predis\Command\Processor\KeyPrefixProcessor');
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
        $clientDef->addTag('snc_redis.client', array('alias' => $client['alias']));
        if (1 === $connectionCount) {
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
        $container->setAlias(sprintf('snc_redis.%s_client', $client['alias']), sprintf('snc_redis.%s', $client['alias']));
    }

    /**
     * Loads a connection.
     *
     * @param string           $clientAlias The client alias
     * @param array            $connection  A connection configuration
     * @param ContainerBuilder $container   A ContainerBuilder instance
     */
    protected function loadPredisConnectionParameters($clientAlias, array $connection, ContainerBuilder $container)
    {
        $parameterId = sprintf('snc_redis.connection.%s_parameters.%s', $connection['alias'], $clientAlias);
        $parameterDef = new Definition($container->getParameter('snc_redis.connection_parameters.class'));
        $parameterDef->setPublic(false);
        $parameterDef->addArgument($connection);
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

        if (1 !== $connectionCount) {
            throw new \RuntimeException('Support for RedisArray is not yet implemented.');
        }

        $dsn = $client['dsns'][0]; /** @var \Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn $dsn */
        $phpredisId = sprintf('snc_redis.phpredis.%s', $client['alias']);

        $phpredisDef = new Definition($container->getParameter('snc_redis.phpredis_client.class'));
        if ($client['logging']) {
            $phpredisDef = new Definition($container->getParameter('snc_redis.phpredis_connection_wrapper.class'));
            $phpredisDef->addArgument(array('alias' => $client['alias']));
            $phpredisDef->addArgument(new Reference('snc_redis.logger'));
        }

        $phpredisDef->addTag('snc_redis.client', array('alias' => $client['alias']));
        $phpredisDef->setPublic(false);
        $connectMethod = $client['options']['connection_persistent'] ? 'pconnect' : 'connect';
        $connectParameters = array();
        if (null !== $dsn->getSocket()) {
            $connectParameters[] = $dsn->getSocket();
            $connectParameters[] = null;
        } else {
            $connectParameters[] = $dsn->getHost();
            $connectParameters[] = $dsn->getPort();
        }
        if ($client['options']['connection_timeout']) {
            $connectParameters[] = $client['options']['connection_timeout'];
        }

        $phpredisDef->addMethodCall($connectMethod, $connectParameters);
        if ($client['options']['prefix']) {
            $phpredisDef->addMethodCall('setOption', array(\Redis::OPT_PREFIX, $client['options']['prefix']));
        }
        if (null !== $dsn->getPassword()) {
            $phpredisDef->addMethodCall('auth', array($dsn->getPassword()));
        }
        if (null !== $dsn->getDatabase()) {
            $phpredisDef->addMethodCall('select', array($dsn->getDatabase()));
        }
        $container->setDefinition($phpredisId, $phpredisDef);

        $container->setAlias(sprintf('snc_redis.%s', $client['alias']), $phpredisId);
        $container->setAlias(sprintf('snc_redis.%s_client', $client['alias']), $phpredisId);
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
        $prefix = $container->getParameter('snc_redis.session.prefix');
        $locking = $container->getParameter('snc_redis.session.locking');
        $spinLockWait = $container->getParameter('snc_redis.session.spin_lock_wait');

        $client = sprintf('snc_redis.%s_client', $client);

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
            if ('second_level_cache' === $name) {
                $name = 'second_level_cache.region_cache_driver';
            }
            $definitionFunction = null;
            switch ($config['clients'][$cache['client']]['type']) {
                case 'predis':
                    $definitionFunction = function ($client, $cache) use ($container) {
                        $def = new Definition($container->getParameter('snc_redis.doctrine_cache_predis.class'));
                        $def->addArgument($client);
                        if ($cache['namespace']) {
                            $def->addMethodCall('setNamespace', array($cache['namespace']));
                        }

                        return $def;
                    };
                    break;
                case 'phpredis':
                    $definitionFunction = function ($client, $cache) use ($container) {
                        $def = new Definition($container->getParameter('snc_redis.doctrine_cache_phpredis.class'));
                        $def->addMethodCall('setRedis', array($client));
                        if ($cache['namespace']) {
                            $def->addMethodCall('setNamespace', array($cache['namespace']));
                        }

                        return $def;
                    };
                    break;
            }

            $client = new Reference(sprintf('snc_redis.%s_client', $cache['client']));
            foreach ($cache['entity_managers'] as $em) {
                $def = call_user_func_array($definitionFunction, array($client, $cache));
                $container->setDefinition(sprintf('doctrine.orm.%s_%s', $em, $name), $def);
            }
            foreach ($cache['document_managers'] as $dm) {
                $def = call_user_func_array($definitionFunction, array($client, $cache));
                $container->setDefinition(sprintf('doctrine.odm.mongodb.%s_%s', $dm, $name), $def);
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
        if ('phpredis' === $config['clients'][$config['monolog']['client']]['type']) {
            $ref = new Reference(sprintf('snc_redis.phpredis.%s', $config['monolog']['client']));
        } else {
            $ref = new Reference(sprintf('snc_redis.%s', $config['monolog']['client']));
        }

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

    /**
     * Loads the profiler storage configuration.
     *
     * @param array            $config    A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader    $loader    A XmlFileLoader instance
     */
    protected function loadProfilerStorage(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('profiler_storage.xml');

        $container->setParameter('snc_redis.profiler_storage.client', $config['profiler_storage']['client']);
        $container->setParameter('snc_redis.profiler_storage.ttl', $config['profiler_storage']['ttl']);

        $client = $container->getParameter('snc_redis.profiler_storage.client');
        $client = sprintf('snc_redis.%s_client', $client);
        $container->setAlias('snc_redis.profiler_storage.client', $client);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
