<?php

namespace Snc\RedisBundle\Factory;

use Snc\RedisBundle\Client\Phpredis\Client;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class PhpredisClientFactory
{
    /**
     * @var RedisLogger|null
     */
    protected $logger;

    /**
     * @param RedisLogger $logger A RedisLogger instance
     */
    public function __construct(RedisLogger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $class   Redis class to instantiate
     * @param string $dsn     One DSN string
     * @param array  $options Options provided in bundle client config
     * @param string $alias   Connection alias provided in bundle client config
     *
     * @return \Redis|Client
     * @throws InvalidConfigurationException
     */
    public function create($class, $dsn, $options, $alias)
    {
        if (!is_a($class, '\Redis', true)) {
            throw new \RuntimeException(sprintf('The factory can only instantiate \Redis classes: %s asked', $class));
        }

        $client            = $this->createClient($class, $alias);
        $parsedDsn         = new RedisDsn($dsn);
        $connectParameters = array();

        if (null !== $parsedDsn->getSocket()) {
            $connectParameters[] = $parsedDsn->getSocket();
            $connectParameters[] = null;
        } else {
            $connectParameters[] = $parsedDsn->getHost();
            $connectParameters[] = $parsedDsn->getPort();
        }

        if (isset($options['connection_timeout'])) {
            $connectParameters[] = $options['connection_timeout'];
        } else {
            $connectParameters[] = null;
        }

        if (!empty($options['connection_persistent'])) {
            $connectParameters[] = $parsedDsn->getPersistentId();
        }

        $connectMethod = !empty($options['connection_persistent']) ? 'pconnect' : 'connect';
        call_user_func_array(array($client, $connectMethod), $connectParameters);

        if (isset($options['prefix'])) {
            $client->setOption(\Redis::OPT_PREFIX, $options['prefix']);
        }

        if (null !== $parsedDsn->getPassword()) {
            $client->auth($parsedDsn->getPassword());
        } elseif (isset($options['parameters']['password'])) {
            $client->auth($options['parameters']['password']);
        }

        if (null !== $parsedDsn->getDatabase()) {
            $client->select($parsedDsn->getDatabase());
        } elseif (isset($options['parameters']['database'])) {
            $client->select($options['parameters']['database']);
        }

        if (isset($options['read_write_timeout'])) {
            $client->setOption(\Redis::OPT_READ_TIMEOUT, (float) $options['read_write_timeout']);
        }

        if (isset($options['serialization'])) {
            $client->setOption(\Redis::OPT_SERIALIZER, $this->loadSerializationType($options['serialization']));
        }

        return $client;
    }

    /**
     * @param string $class Redis class to instantiate
     * @param string $alias Connection alias provided in bundle client config
     *
     * @return \Redis|Client
     */
    private function createClient($class, $alias)
    {
        if (is_a($class, '\Snc\RedisBundle\Client\Phpredis\Client', true)) {
            $client = new $class(array('alias' => $alias), $this->logger);
        } else {
            $client = new $class();
        }

        return $client;
    }

    /**
     * Load the correct serializer for Redis
     *
     * @param string $type
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    private function loadSerializationType($type)
    {
        $types = array(
            'default' => \Redis::SERIALIZER_NONE,
            'none' => \Redis::SERIALIZER_NONE,
            'php' => \Redis::SERIALIZER_PHP
        );

        if (defined('Redis::SERIALIZER_IGBINARY')) {
            $types['igbinary'] = \Redis::SERIALIZER_IGBINARY;
        }

        if (array_key_exists($type, $types)) {
            return $types[$type];
        }

        throw new InvalidConfigurationException(sprintf('%s in not a valid serializer. Valid serializers: %s', $type, implode(', ', array_keys($types))));
    }
}
