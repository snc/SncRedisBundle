<?php

namespace Bundle\RedisBundle\DependencyInjection;

use Symfony\Components\DependencyInjection\Extension\Extension;
use Symfony\Components\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Components\DependencyInjection\ContainerBuilder;

class RedisExtension extends Extension
{
    /**
     * Loads the configuration.
     *
     * @param array $config An array of configuration settings
     * @param \Symfony\Components\DependencyInjection\ContainerBuilder $container A ContainerBuilder instance
     */
    public function configLoad($config, ContainerBuilder $container)
    {
        if (!$container->hasDefinition('redis')) {
            $loader = new XmlFileLoader($container, __DIR__ . '/../Resources/config');
            $loader->load('redis.xml');
        }
        if (isset($config['host'])) {
            $container->setParameter('redis.connection.host', $config['host']);
        }
        if (isset($config['port'])) {
            $container->setParameter('redis.connection.port', $config['port']);
        }
        if (isset($config['database'])) {
            $container->setParameter('redis.database.number', $config['database']);
        }
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/symfony';
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../Resources/config/';
    }

    /**
     * Returns the recommended alias to use in XML.
     *
     * This alias is also the mandatory prefix to use when using YAML.
     *
     * @return string The alias
     */
    public function getAlias()
    {
        return 'redis';
    }
}
