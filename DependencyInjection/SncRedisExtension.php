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

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * SncRedisExtension
 */
class SncRedisExtension extends Extension
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

        $config = $this->processConfiguration(new Configuration(), $configs);

        foreach ($config['class'] as $name => $class) {
            $container->setParameter(sprintf('snc_redis.%s.class', $name), $class);
        }

        $connectionFactoryDef = new Definition($container->getParameter('snc_redis.connection_factory.class'));
        $connectionFactoryDef->setPublic(false);
        $connectionFactoryDef->setScope(ContainerInterface::SCOPE_CONTAINER);
        $connectionFactoryDef->addMethodCall('setConnectionWrapperClass', array($container->getParameter('snc_redis.connection_wrapper.class')));
        $connectionFactoryDef->addMethodCall('setLogger', array(new Reference('snc_redis.logger')));
        $container->setDefinition('snc_redis.connectionfactory', $connectionFactoryDef);

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

        if (isset($config['monolog'])) {
            $this->loadMonolog($config, $container);
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
        $parameterId = sprintf('snc_redis.connection.%s_parameters', $connection['alias']);
        $parameterDef = new Definition($container->getParameter('snc_redis.connection_parameters.class'));
        $parameterDef->setPublic(false);
        $parameterDef->setScope(ContainerInterface::SCOPE_CONTAINER);
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
        $optionId = sprintf('snc_redis.client.%s_options', $client['alias']);
        $optionDef = new Definition($container->getParameter('snc_redis.client_options.class'));
        $optionDef->setPublic(false);
        $optionDef->setScope(ContainerInterface::SCOPE_CONTAINER);
        $client['options']['connections'] = new Reference('snc_redis.connectionfactory');
        if (null === $client['options']['cluster']) {
            unset($client['options']['cluster']);
        }
        $optionDef->addArgument($client['options']);
        $container->setDefinition($optionId, $optionDef);
        $clientDef = new Definition($container->getParameter('snc_redis.client.class'));
        $clientDef->setScope(ContainerInterface::SCOPE_CONTAINER);
        if (1 === count($client['connections'])) {
            $clientDef->addArgument(new Reference(sprintf('snc_redis.connection.%s_parameters', $client['connections'][0])));
        } else {
            $connections = array();
            foreach ($client['connections'] as $name) {
                $connections[] = new Reference(sprintf('snc_redis.connection.%s_parameters', $name));
            }
            $clientDef->addArgument($connections);
        }
        $clientDef->addArgument(new Reference($optionId));
        $container->setDefinition(sprintf('snc_redis.%s_client', $client['alias']), $clientDef);
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

        $container->setParameter('snc_redis.session.client', $config['session']['client']);
        $container->setParameter('snc_redis.session.prefix', $config['session']['prefix']);

        $container->setAlias('snc_redis.session.client', sprintf('snc_redis.%s_client', $container->getParameter('snc_redis.session.client')));
        $container->setAlias('session.storage', 'snc_redis.session.storage');
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
            $client = new Reference(sprintf('snc_redis.%s_client', $cache['client']));
            foreach ($cache['entity_managers'] as $em) {
                $def = new Definition($container->getParameter('snc_redis.doctrine_cache.class'));
                $def->setScope(ContainerInterface::SCOPE_CONTAINER);
                $def->addMethodCall('setRedis', array($client));
                if ($cache['namespace']) {
                    $def->addMethodCall('setNamespace', array($cache['namespace']));
                }
                $container->setDefinition(sprintf('doctrine.orm.%s_%s', $em, $name), $def);
            }
            foreach ($cache['document_managers'] as $dm) {
                $def = new Definition($container->getParameter('snc_redis.doctrine_cache.class'));
                $def->setScope(ContainerInterface::SCOPE_CONTAINER);
                $def->addMethodCall('setRedis', array($client));
                if ($cache['namespace']) {
                    $def->addMethodCall('setNamespace', array($cache['namespace']));
                }
                $container->setDefinition(sprintf('doctrine.odm.mongodb.%s_%s', $dm, $name), $def);
            }
        }
    }

    /**
     * Loads the Monolog configuration.
     *
     * @param array $config A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadMonolog(array $config, ContainerBuilder $container)
    {
        $def = new Definition($container->getParameter('snc_redis.monolog_handler.class'));
        $def->setPublic(false);
        $clientDef = new Definition($container->getParameter('snc_redis.client.class'));
        $clientDef->setPublic(false);
        $clientDef->addArgument(new Reference(sprintf('snc_redis.connection.%s_parameters', $config['monolog']['connection'])));
        $container->setDefinition('snc_redis.monolog_client', $clientDef);
        $def->addMethodCall('setRedis', array(new Reference('snc_redis.monolog_client')));
        $def->addMethodCall('setKey', array($config['monolog']['key']));
        $container->setDefinition('monolog.handler.redis', $def);
    }
}
