<?php

namespace Snc\RedisBundle\Factory;

use Snc\RedisBundle\Client\Phpredis\ClientCluster;
use Snc\RedisBundle\Client\Phpredis\Client;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * @internal
 */
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
     * @param array  $dsns    Multiple DSN string
     * @param array  $options Options provided in bundle client config
     * @param string $alias   Connection alias provided in bundle client config
     *
     * @return \Redis|Client|\RedisCluster|ClientCluster
     * @throws InvalidConfigurationException
     * @throws \LogicException
     */
    public function create($class, array $dsns, $options, $alias)
    {
        if (!is_a($class, \Redis::class, true)
            && !is_a($class, \RedisCluster::class, true)
        ) {
            throw new \LogicException(sprintf('The factory can only instantiate \Redis|\RedisCluster classes: "%s" asked', $class));
        }

        // Normalize the DSNs, because using processed environment variables could lead to nested values.
        $dsns = count($dsns) === 1 && is_array($dsns[0]) ? $dsns[0] : $dsns;

        $parsedDsns = array_map(static function (string $dsn) {
            return new RedisDsn($dsn);
        }, $dsns);

        if (is_a($class, \Redis::class, true)) {
            if (count($parsedDsns) > 1) {
                throw new \LogicException('Cannot have more than 1 dsn with \Redis and \RedisArray is not supported yet.');
            }

            return $this->createClient($parsedDsns[0], $class, $alias, $options);
        }

        return $this->createClusterClient($parsedDsns, $class, $alias, $options);
    }

    /**
     * @param RedisDsn[] $dsns
     * @param string     $class Redis class to instantiate
     * @param string     $alias Connection alias provided in bundle client config
     * @param array      $options
     *
     * @return \RedisCluster|ClientCluster
     *
     * @throws InvalidConfigurationException
     * @throws \LogicException
     */
    private function createClusterClient(array $dsns, $class, $alias, array $options): \RedisCluster
    {
        $args = [];

        if (is_a($class, ClientCluster::class, true)) {
            $args[] = ['alias' => $alias];
            $args[] = $this->logger;
        } else {
            $args[] = null;
        }

        $seeds = [];
        foreach ($dsns as $dsn) {
            $seeds[] = ($dsn->getTls() ? 'tls://' : '') . $dsn->getHost() . ':' . $dsn->getPort();
        }

        $args[] = $seeds;
        $args[] = $options['connection_timeout'] ?? null;
        $args[] = $options['read_write_timeout'] ?? null;
        $args[] = (bool) ($options['connection_persistent'] ?? null);

        $password = $options['parameters']['password'] ?? null;
        if (version_compare(phpversion('redis'), '4.3.0', '>=')) {
            $args[] = $password;
        } elseif ($password) {
            throw new \LogicException('Your phpredis version "'.phpversion('redis').'" does not support \RedisCluster password authentication, you need at least "4.3.0"');
        }

        $client = new $class(...$args);

        if (isset($options['prefix'])) {
            $client->setOption(\RedisCluster::OPT_PREFIX, $options['prefix']);
        }

        if (isset($options['serialization'])) {
            $client->setOption(\RedisCluster::OPT_SERIALIZER, $this->loadSerializationType($options['serialization']));
        }

        return $client;
    }

    /**
     * @param RedisDsn $dsn
     * @param string   $class   Redis class to instantiate
     * @param string   $alias   Connection alias provided in bundle client config
     * @param array    $options
     *
     * @return \Redis|Client
     * @throws InvalidConfigurationException
     */
    private function createClient(RedisDsn $dsn, $class, $alias, array $options): \Redis
    {
        /** @var \Redis $client */
        if (is_a($class, Client::class, true)) {
            $client = new $class(['alias' => $alias], $this->logger);
        } else {
            $client = new $class();
        }

        $connectParameters = array();

        if (null !== $dsn->getSocket()) {
            $connectParameters[] = $dsn->getSocket();
            $connectParameters[] = null;
        } else {
            $connectParameters[] = ($dsn->getTls() ? 'tls://' : '') . $dsn->getHost();
            $connectParameters[] = $dsn->getPort();
        }

        if (isset($options['connection_timeout'])) {
            $connectParameters[] = $options['connection_timeout'];
        } else {
            $connectParameters[] = null;
        }

        if (!empty($options['connection_persistent'])) {
            $connectParameters[] = $dsn->getPersistentId();
        }

        if (!empty($options['connection_persistent'])) {
            $client->pconnect(...$connectParameters);
        } else {
            $client->connect(...$connectParameters);
        }

        $password = $dsn->getPassword() ?? $options['parameters']['password'] ?? null;
        if ($password) {
            $client->auth($password);
        }

        $db = $dsn->getDatabase() ?? $options['parameters']['database'] ?? null;
        if (null !== $db && $db !== '') {
            $client->select($db);
        }

        if (isset($options['prefix'])) {
            $client->setOption(\Redis::OPT_PREFIX, $options['prefix']);
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
     * Load the correct serializer for Redis
     *
     * @param string $type
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    private function loadSerializationType($type)
    {
        $types = [
            'default' => \Redis::SERIALIZER_NONE,
            'none' => \Redis::SERIALIZER_NONE,
            'php' => \Redis::SERIALIZER_PHP
        ];

        if (defined('Redis::SERIALIZER_IGBINARY')) {
            $types['igbinary'] = \Redis::SERIALIZER_IGBINARY;
        }

        if (array_key_exists($type, $types)) {
            return $types[$type];
        }

        throw new InvalidConfigurationException(sprintf('%s in not a valid serializer. Valid serializers: %s', $type, implode(', ', array_keys($types))));
    }
}
