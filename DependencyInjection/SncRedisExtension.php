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
     * @param array $client A client configuration
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
     * @param array $client A client configuration
     * @param ContainerBuilder $container
     */
    protected function loadPredisClient(array $client, ContainerBuilder $container)
    {
        $connections = array();
        $connectionCount = count($client['dsns']);
        foreach ($client['dsns'] as $i => $dsn) {
            /** @var \Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn $dsn */
            $connectionAlias = 1 === $connectionCount ? $client['alias'] : $client['alias'] . ($i + 1);
            $connections[] = $connectionAlias;
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
            $connection['database'] = $dsn->getDatabase();
            $connection['password'] = $dsn->getPassword();
            $connection['weight'] = $dsn->getWeight();
            $this->loadPredisConnectionParameters($connection, $container);
        }

        $optionId = sprintf('snc_redis.client.%s_options', $client['alias']);
        $optionDef = new Definition($container->getParameter('snc_redis.client_options.class'));
        $optionDef->setPublic(false);
        $optionDef->setScope(ContainerInterface::SCOPE_CONTAINER);
        if ($client['logging']) {
            $client['options']['connections'] = new Reference('snc_redis.connectionfactory');
        }
        if (null === $client['options']['cluster']) {
            unset($client['options']['cluster']);
        }
        $optionDef->addArgument($client['options']);
        $container->setDefinition($optionId, $optionDef);
        $clientDef = new Definition($container->getParameter('snc_redis.client.class'));
        $clientDef->setScope(ContainerInterface::SCOPE_CONTAINER);
        if (1 === $connectionCount) {
            $clientDef->addArgument(new Reference(sprintf('snc_redis.connection.%s_parameters', $connections[0])));
        } else {
            $connections = array();
            foreach ($connections as $name) {
                $connections[] = new Reference(sprintf('snc_redis.connection.%s_parameters', $name));
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
     * @param array $connection A connection configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadPredisConnectionParameters(array $connection, ContainerBuilder $container)
    {
        $parameterId = sprintf('snc_redis.connection.%s_parameters', $connection['alias']);
        $parameterDef = new Definition($container->getParameter('snc_redis.connection_parameters.class'));
        $parameterDef->setPublic(false);
        $parameterDef->setScope(ContainerInterface::SCOPE_CONTAINER);
        $parameterDef->addArgument($connection);
        $container->setDefinition($parameterId, $parameterDef);
    }

    /**
     * Loads a redis client using phpredis.
     *
     * @param array $client A client configuration
     * @param ContainerBuilder $container
     */
    protected function loadPhpredisClient(array $client, ContainerBuilder $container)
    {
        $connectionCount = count($client['dsns']);

        if (1 !== $connectionCount) {
            throw new \RuntimeException('Support for RedisArray is not yet implemented.');
        }

        $dsn = $client['dsns'][0]; /** @var \Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn $dsn */

        $phpredisId = sprintf('snc_redis.phpredis.%s', $client['alias']);
        $phpredisDef = new Definition('Redis'); // TODO $container->getParameter('snc_redis.*.class')
        $phpredisDef->setPublic(true);
        $phpredisDef->setScope(ContainerInterface::SCOPE_CONTAINER);
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
        if (null !== $dsn->getPassword()) {
            $phpredisDef->addMethodCall('auth', array($dsn->getPassword()));
        }
        $phpredisDef->addMethodCall('select', array($dsn->getDatabase()));
        $container->setDefinition($phpredisId, $phpredisDef);

        if ($client['logging']) {
            $phpredisDef->setPublic(false);
            $parameters = array('alias' => $client['alias']);
            $clientDef = new Definition('Snc\RedisBundle\Client\Phpredis\Client'); // TODO $container->getParameter('snc_redis.*.class')
            $clientDef->setScope(ContainerInterface::SCOPE_CONTAINER);
            $clientDef->addArgument($parameters);
            $clientDef->addArgument(new Reference('snc_redis.logger'));
            $clientDef->addMethodCall('setRedis', array(new Reference($phpredisId)));
            $container->setDefinition(sprintf('snc_redis.%s', $client['alias']), $clientDef);
        } else {
            $container->setAlias(sprintf('snc_redis.%s', $client['alias']), $phpredisId);
        }

        $container->setAlias(sprintf('snc_redis.%s_client', $client['alias']), sprintf('snc_redis.%s', $client['alias']));
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

        if (isset($config['clients'][$config['session']['client']]) && 'phpredis' === $config['clients'][$config['session']['client']]['type']) {
            throw new \LogicException('Please use the native session support of phpredis.');
        }

        $container->setParameter('snc_redis.session.client', $config['session']['client']);
        $container->setParameter('snc_redis.session.prefix', $config['session']['prefix']);

        $container->setAlias('snc_redis.session.client', sprintf('snc_redis.%s_client', $container->getParameter('snc_redis.session.client')));

        if ($config['session']['use_as_default']) {
            $container->setAlias('session.handler', 'snc_redis.session.handler');
        }
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
        $def->addMethodCall('setRedis', array(new Reference(sprintf('snc_redis.%s', $config['monolog']['client']))));
        $def->addMethodCall('setKey', array($config['monolog']['key']));
        $container->setDefinition('monolog.handler.redis', $def);
    }

    /**
     * Loads the SwiftMailer configuration.
     *
     * @param array $config A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadSwiftMailer(array $config, ContainerBuilder $container)
    {
        $def = new Definition($container->getParameter('snc_redis.swiftmailer_spool.class'));
        $def->setPublic(false);
        $def->addMethodCall('setRedis', array(new Reference(sprintf('snc_redis.%s', $config['swiftmailer']['client']))));
        $def->addMethodCall('setKey', array($config['swiftmailer']['key']));
        $container->setDefinition('snc_redis.swiftmailer.spool', $def);
        $container->setAlias('swiftmailer.spool', 'snc_redis.swiftmailer.spool');
    }
}
