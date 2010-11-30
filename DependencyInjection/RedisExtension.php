<?php

namespace Bundle\RedisBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * RedisExtension
 */
class RedisExtension extends Extension
{
    /**
     * Loads the configuration.
     *
     * @param array $config An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function configLoad($config, ContainerBuilder $container)
    {
        if (!$container->hasDefinition('redis')) {
            $loader = new XmlFileLoader($container, __DIR__ . '/../Resources/config');
            $loader->load('redis.xml');
        }
        if (isset($config['host'])) {
            $container->setParameter('redis.connection.host', (string) $config['host']);
        }
        if (isset($config['port'])) {
            $container->setParameter('redis.connection.port', (int) $config['port']);
        }
        if (isset($config['database'])) {
            $container->setParameter('redis.database.number', (int) $config['database']);
        }
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
