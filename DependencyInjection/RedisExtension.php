<?php

namespace Bundle\RedisBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * RedisExtension
 */
class RedisExtension extends Extension
{
    /**
     * Loads the configuration.
     *
     * @param array $configs An array of configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function configLoad(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('redis.xml');

        $config = $this->mergeConfig($configs, $container);

        foreach ($config['connections'] as $name => $connection) {
            $this->loadConnection($connection, $container);
        }

        foreach ($config['clients'] as $name => $client) {
            $this->loadClient($client, $container);
        }
    }

    /**
     * Merges a set of configurations.
     *
     * @param array $configs An array of configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @return array A merged configuration array
     */
    protected function mergeConfig(array $configs, ContainerBuilder $container)
    {
        $mergedConfig = array(
            'connections' => array(),
            'clients' => array(),
        );

        $connectionDefaults = array(
            'scheme' => 'tcp',
            'host' => 'localhost',
            'port' => 6379,
            'path' => null,
            'database' => 0,
            'password' => null,
            'connection_async' => false,
            'connection_persistent' => false,
            'connection_timeout' => 5,
            'read_write_timeout' => null,
            'weight' => null,
            'logging' => false,
        );

        $clientDefaults = array(
            'connection' => null,
        );

        foreach ($configs as $config) {
            if (isset($config['connections'])) {
                foreach ($config['connections'] as $name => $connection) {
                    if (!isset($mergedConfig['connections'][$name])) {
                        $mergedConfig['connections'][$name] = $connectionDefaults;
                    }
                    $mergedConfig['connections'][$name]['alias'] = $name;
                    foreach ($connection as $k => $v) {
                        if (array_key_exists($k, $connectionDefaults)) {
                            $mergedConfig['connections'][$name][$k] = $v;
                        }
                    }
                }
            }
            if (isset($config['clients'])) {
                foreach ($config['clients'] as $name => $client) {
                    if (!isset($mergedConfig['clients'][$name])) {
                        $mergedConfig['clients'][$name] = $clientDefaults;
                    }
                    $mergedConfig['clients'][$name]['alias'] = $name;
                    if (null !== $client) {
                        foreach ($client as $k => $v) {
                            if (array_key_exists($k, $clientDefaults)) {
                                $mergedConfig['clients'][$name][$k] = $v;
                            }
                        }
                    }
                }
            }
        }

        return $mergedConfig;
    }

    /**
     * Loads a connection.
     *
     * @param array $connection A connection configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadConnection(array $connection, ContainerBuilder $container)
    {
        $parameterId = sprintf('redis.connection.%s_parameters', $connection['alias']);
        $parameterDef = new Definition($container->getParameter('redis.connection_parameters.class'));
        $parameterDef->setPublic(false);
        $parameterDef->addArgument($connection);
        $container->setDefinition($parameterId, $parameterDef);
        $connectionDef = new Definition($container->getParameter('redis.connection.class'));
        $connectionDef->setPublic(false);
        $connectionDef->addArgument(new Reference($parameterId));
        if (isset($connection['logging']) && $connection['logging']) {
            $connectionDef->addArgument(new Reference('redis.logger'));
        }
        $container->setDefinition(sprintf('redis.connection.%s', $connection['alias']), $connectionDef);
    }

    /**
     * Loads a redis client.
     *
     * @param array $client A client configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadClient(array $client, ContainerBuilder $container)
    {
        $containerDef = new Definition($container->getParameter('redis.client.class'));
        if (null === $client['connection']) {
            $client['connection'] = array($client['alias']);
        } else if (is_string($client['connection'])) {
            $client['connection'] = array($client['connection']);
        }
        if (1 === count($client['connection'])) {
            $containerDef->addArgument(new Reference(sprintf('redis.connection.%s', $client['connection'][0])));
        } else {
            $connections = array();
            foreach ($client['connection'] as $name) {
                $connections[] = new Reference(sprintf('redis.connection.%s', $name));
            }
            $containerDef->addArgument($connections);
        }
        $container->setDefinition(sprintf('redis.%s_client', $client['alias']), $containerDef);
    }

    /**
     * Loads the session configuration.
     *
     * @param array $configs An array of configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function sessionLoad(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('session.xml');

        $config = $this->flattenConfigs($configs);

        if (isset($config['client'])) {
            $container->setParameter('redis.session.client', $config['client']);
        }
        if (isset($config['prefix'])) {
            $container->setParameter('redis.session.prefix', $config['prefix']);
        }

        $container->setAlias('redis.session.client', sprintf('redis.%s_client', $container->getParameter('redis.session.client')));
        $container->setAlias('session.storage', 'session.storage.redis');
    }

    /**
     * Loads the Doctrine configuration.
     *
     * @param array $configs An array of configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function doctrineLoad(array $configs, ContainerBuilder $container)
    {
        $config = $this->flattenConfigs($configs);

        $clientName = isset($config['client']) ? $config['client'] : 'cache';
        unset($config['client']);

        foreach ($config as $cacheType => $configBlock) {
            foreach ((array) $configBlock as $name) {
                $def = new Definition($container->getParameter('doctrine.orm.cache.redis_class'));
                $def->addMethodCall('setRedis', array(new Reference(sprintf('redis.%s_client', $clientName))));
                $container->setDefinition(sprintf('doctrine.orm.%s_%s', $name, $cacheType), $def);
            }
        }
    }

    /**
     * Temporary function to merge session/doctrine configurations.
     *
     * @param array $configs An array of configurations
     * @return array A merged configuration array
     */
    protected function flattenConfigs(array $configs)
    {
        $config = array();
        foreach ($configs as $conf) {
            $config = array_merge($config, $conf);
        }
        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/redis';
    }

    /**
     * {@inheritdoc}
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'redis';
    }
}
