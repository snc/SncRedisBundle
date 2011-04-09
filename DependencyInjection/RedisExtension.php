<?php

namespace Snc\RedisBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
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
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('redis.xml');

        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);

        foreach ($config['class'] as $name => $class) {
            $container->setParameter(sprintf('redis.%s.class', $name), $class);
        }

        $schemesMap = array(
            'tcp' => $container->getParameter('redis.connection.class'),
            'unix' => $container->getParameter('redis.connection.class'),
        );

        $connectionFactoryDef = new Definition($container->getParameter('redis.connection-factory.class'));
        $connectionFactoryDef->setPublic(false);
        $connectionFactoryDef->setScope('container');
        $connectionFactoryDef->addArgument($schemesMap);
        $connectionFactoryDef->addMethodCall('setLogger', array(new Reference('redis.logger')));
        $container->setDefinition('redis.connectionfactory', $connectionFactoryDef);

        foreach ($config['connections'] as $connection) {
            $this->loadConnection($connection, $container);
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
        $parameterDef->setScope('container');
        $parameterDef->addArgument($connection);
        $container->setDefinition($parameterId, $parameterDef);
    }

    /**
     * Loads a redis client.
     *
     * @param array $client A client configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadClient(array $client, ContainerBuilder $container)
    {
        $optionId = sprintf('redis.client.%s_options', $client['alias']);
        $optionDef = new Definition($container->getParameter('redis.client_options.class'));
        $optionDef->setPublic(false);
        $optionDef->setScope('container');
        $client['options']['connections'] = new Reference('redis.connectionfactory');
        $optionDef->addArgument($client['options']);
        $container->setDefinition($optionId, $optionDef);
        $clientDef = new Definition($container->getParameter('redis.client.class'));
        $clientDef->setScope('container');
        if (1 === count($client['connections'])) {
            $clientDef->addArgument(new Reference(sprintf('redis.connection.%s_parameters', $client['connections'][0])));
        } else {
            $connections = array();
            foreach ($client['connections'] as $name) {
                $connections[] = new Reference(sprintf('redis.connection.%s_parameters', $name));
            }
            $clientDef->addArgument($connections);
        }
        $clientDef->addArgument(new Reference($optionId));
        $container->setDefinition(sprintf('redis.%s_client', $client['alias']), $clientDef);
    }

    /**
     * Loads the session configuration.
     *
     * @param array $config A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader $loader
     */
    protected function loadSession(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('session.xml');

        $container->setParameter('redis.session.client', $config['session']['client']);
        $container->setParameter('redis.session.prefix', $config['session']['prefix']);

        $container->setAlias('redis.session.client', sprintf('redis.%s_client', $container->getParameter('redis.session.client')));
        $container->setAlias('session.storage', 'session.storage.redis');
    }

    /**
     * Loads the Doctrine configuration.
     *
     * @param array $config A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDoctrine(array $config, ContainerBuilder $container)
    {
        foreach ($config['doctrine'] as $name => $cache) {
            $client = new Reference(sprintf('redis.%s_client', $cache['client']));
            foreach ($cache['entity_managers'] as $em) {
                $def = new Definition($container->getParameter('redis.doctrine_cache.class'));
                $def->setScope('container');
                $def->addMethodCall('setRedis', array($client));
                if ($cache['namespace']) {
                    $def->addMethodCall('setNamespace', array($cache['namespace']));
                }
                $container->setDefinition(sprintf('doctrine.orm.%s_%s', $em, $name), $def);
            }
            foreach ($cache['document_managers'] as $dm) {
                $def = new Definition($container->getParameter('redis.doctrine_cache.class'));
                $def->setScope('container');
                $def->addMethodCall('setRedis', array($client));
                if ($cache['namespace']) {
                    $def->addMethodCall('setNamespace', array($cache['namespace']));
                }
                $container->setDefinition(sprintf('doctrine.odm.mongodb.%s_%s', $dm, $name), $def);
            }
        }
    }
}
