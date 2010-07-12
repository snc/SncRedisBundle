<?php

namespace Bundle\RedisBundle\DependencyInjection;

use Symfony\Components\DependencyInjection\Loader\LoaderExtension;
use Symfony\Components\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Components\DependencyInjection\BuilderConfiguration;

class RedisExtension extends LoaderExtension
{
    /**
     * Loads the configuration.
     *
     * @param array $config A configuration array
     * @param BuilderConfiguration $configuration A BuilderConfiguration instance
     */
    public function configLoad($config, BuilderConfiguration $configuration)
    {
        if (!$configuration->hasDefinition('redis')) {
            $loader = new XmlFileLoader(__DIR__ . '/../Resources/config');
            $configuration->merge($loader->load('redis.xml'));
        }
        if (isset($config['host'])) {
            $configuration->setParameter('redis.connection.host', $config['host']);
        }
        if (isset($config['port'])) {
            $configuration->setParameter('redis.connection.port', $config['port']);
        }
        if (isset($config['database'])) {
            $configuration->setParameter('redis.database.number', $config['database']);
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
