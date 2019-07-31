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
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisEnvDsn;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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
        } else {
            unset($client['options']['replication']);
        }

        if (isset($client['options']['replication']) && false === $client['options']['replication']) {
            @trigger_error(
                'Option "replication" with value "false" is deprecated since 2.1.9, to be removed in 3.0. Please choose a valid value or remove this option.',
                E_USER_DEPRECATED
            );

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
        $clientDef->setPublic(true);
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

        $clientDefId = sprintf('snc_redis.%s', $client['alias']);
        $clientAliasId = sprintf('snc_redis.%s_client', $client['alias']);

        $clientDef->addArgument(new Reference($optionId));
        $container->setDefinition($clientDefId, $clientDef);
        $container->setAlias($clientAliasId, $clientDefId);
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
        $parameterDef->setFactory(array('Snc\RedisBundle\Factory\PredisParametersFactory', 'create'));
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

        if (1 !== $connectionCount) {
            throw new \RuntimeException('Support for RedisArray is not yet implemented.');
        }

        /** @var \Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn $dsn */
        $dsn = $client['dsns'][0];

        $phpRedisVersion = phpversion('redis');
        if (version_compare($phpRedisVersion, '4.0.0') >= 0 && $client['logging']) {
            $client['logging'] = false;
            @trigger_error(sprintf('Redis logging is not supported on PhpRedis %s and has been automatically disabled, disable logging in config to suppress this warning', $phpRedisVersion), E_USER_WARNING);
        }

        $phpredisClientclass = $container->getParameter('snc_redis.phpredis_client.class');
        if ($client['logging']) {
            $phpredisClientclass = $container->getParameter('snc_redis.phpredis_connection_wrapper.class');
        }
        $phpredisDef = new Definition($phpredisClientclass);
        $phpredisDef->setFactory(array(
            new Definition('Snc\RedisBundle\Factory\PhpredisClientFactory', array(new Reference('snc_redis.logger'))),
            'create'
        ));
        $phpredisDef->addArgument($phpredisClientclass);
        $phpredisDef->addArgument((string) $dsn);
        $phpredisDef->addArgument($client['options']);
        $phpredisDef->addArgument($client['alias']);
        $phpredisDef->addTag('snc_redis.client', array('alias' => $client['alias']));
        $phpredisDef->setPublic(false);
        
        // Older version of phpredis extension do not support lazy loading
        $minimumVersionForLazyLoading = '4.1.1';
        $supportsLazyServices = version_compare($phpRedisVersion, $minimumVersionForLazyLoading, '>=');
        $phpredisDef->setLazy($supportsLazyServices);
        if (!$supportsLazyServices) {
            @trigger_error(
                sprintf('Lazy loading Redis is not supported on PhpRedis %s. Please update to PhpRedis %s or higher.', $phpRedisVersion, $minimumVersionForLazyLoading), 
                E_USER_WARNING
            );    
        }

        $phpredisId = sprintf('snc_redis.phpredis.%s', $client['alias']);
        $phpredisAliasId = sprintf('snc_redis.%s_client', $client['alias']);
        $phpredisAliasId2 = sprintf('snc_redis.%s', $client['alias']);

        $container->setDefinition($phpredisId, $phpredisDef);
        $container->setAlias($phpredisAliasId2, new Alias($phpredisId, true));
        $alias = $container->setAlias($phpredisAliasId, $phpredisId);

        if (method_exists($alias, 'setDeprecated')) {
            $alias->setDeprecated(true, '"%alias_id%" service is deprecated since 2.1.10, to be removed in 3.0. Please use "'.$phpredisAliasId2.'" instead.');
        }
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

            $client = new Reference(sprintf('snc_redis.%s', $cache['client']));
            foreach ($cache['entity_managers'] as $em) {
                $def = call_user_func_array($definitionFunction, array($client, $cache));
                $container->setDefinition(sprintf('doctrine.orm.%s_%s', $em, $name), $def);
            }
            foreach ($cache['document_managers'] as $dm) {
                $def = call_user_func_array($definitionFunction, array($client, $cache));
                $container->setDefinition(sprintf('doctrine_mongodb.odm.%s_%s', $dm, $name), $def);
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

     /* Loads the profiler storage configuration.
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
        $client = sprintf('snc_redis.%s', $client);
        $container->setAlias('snc_redis.profiler_storage.client', $client);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
