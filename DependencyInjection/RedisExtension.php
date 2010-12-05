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
        if (isset($config['servers'])) {
            $container->setParameter('redis.connection.servers', $config['servers']);
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
     * Loads the configuration.
     *
     * @param array $config An array of configuration settings
     * @param \Symfony\Components\DependencyInjection\ContainerBuilder $container A ContainerBuilder instance
     */
    public function sessionLoad($config, ContainerBuilder $container)
    {
        if (!$container->hasDefinition('session.storage.redis')) {
            $loader = new XmlFileLoader($container, __DIR__ . '/../Resources/config');
            $loader->load('session.xml');
        }
        
        foreach ($config AS $key => $value) {
            $container->setParameter('session.storage.redis.options.' . $key, $value);
        }
        
        $container->setAlias('session.storage', 'session.storage.redis');
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
