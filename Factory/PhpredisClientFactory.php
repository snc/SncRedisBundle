<?php

namespace Snc\RedisBundle\Factory;

use ProxyManager\Configuration;
use ProxyManager\Factory\AccessInterceptorScopeLocalizerFactory;
use ProxyManager\Proxy\AccessInterceptorInterface;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @internal
 */
class PhpredisClientFactory
{
    /**
     * These shouldn't be logged, because they don't actually contact redis server
     */
    private const CLIENT_ONLY_COMMANDS = [
        'getOption',
        'setOption',
        'getDbNum',
        'getPersistentID',
    ];
    /**
     * @var RedisLogger
     */
    private $logger;
    /**
     * @var Stopwatch|null
     */
    private $stopwatch;
    /**
     * @var Configuration|null
     */
    private $proxyConfiguration;

    /**
     * @param RedisLogger $logger A RedisLogger instance
     */
    public function __construct(RedisLogger $logger, ?Configuration $proxyConfiguration = null, ?Stopwatch $stopwatch = null) {
        $this->logger = $logger;
        $this->stopwatch = $stopwatch;

        if ($this->proxyConfiguration = $proxyConfiguration) {
            spl_autoload_register($this->proxyConfiguration->getProxyAutoloader());
        }
    }

    /**
     * @param string $class   Redis class to instantiate
     * @param array  $dsns    Multiple DSN string
     * @param array  $options Options provided in bundle client config
     * @param string $alias   Connection alias provided in bundle client config
     *
     * @return \Redis|\RedisCluster
     * @throws InvalidConfigurationException
     * @throws \LogicException
     */
    public function create(string $class, array $dsns, array $options, string $alias, bool $loggingEnabled)
    {
        if (!is_a($class, \Redis::class, true) && !is_a($class, \RedisCluster::class, true)) {
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

            return $this->createClient($parsedDsns[0], $class, $alias, $options, $loggingEnabled);
        }

        return $this->createClusterClient($parsedDsns, $class, $alias, $options, $loggingEnabled);
    }

    /**
     * @param RedisDsn[] $dsns
     * @param string     $class Redis class to instantiate
     * @param string     $alias Connection alias provided in bundle client config
     * @param array      $options
     *
     * @throws InvalidConfigurationException
     * @throws \LogicException
     */
    private function createClusterClient(array $dsns, string $class, string $alias, array $options, bool $loggingEnabled): \RedisCluster
    {
        $args = [null];

        $seeds = [];
        foreach ($dsns as $dsn) {
            $seeds[] = ($dsn->getTls() ? 'tls://' : '') . $dsn->getHost() . ':' . $dsn->getPort();
        }

        $args[] = $seeds;
        $args[] = $options['connection_timeout'];
        $args[] = $options['read_write_timeout'] ?? 0;
        $args[] = (bool)$options['connection_persistent'];
        $args[] = $options['parameters']['password'] ?? null;

        $client = new $class(...$args);

        if (isset($options['prefix'])) {
            $client->setOption(\RedisCluster::OPT_PREFIX, $options['prefix']);
        }

        if (isset($options['serialization'])) {
            $client->setOption(\RedisCluster::OPT_SERIALIZER, $this->loadSerializationType($options['serialization']));
        }

        if (isset($options['slave_failover'])) {
            $client->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, $this->loadSlaveFailoverType($options['slave_failover']));
        }

        return $loggingEnabled ? $this->createLoggingProxy($client, $alias) : $client;
    }

    /**
     * @param RedisDsn $dsn
     * @param string   $class   Redis class to instantiate
     * @param string   $alias   Connection alias provided in bundle client config
     * @param array    $options
     *
     * @throws InvalidConfigurationException
     */
    private function createClient(RedisDsn $dsn, string $class, string $alias, array $options, bool $loggingEnabled): \Redis
    {
        $client = new $class();

        if ($loggingEnabled) {
            $client = $this->createLoggingProxy($client, $alias);
        }

        $connectParameters = array();

        if (null !== $dsn->getSocket()) {
            $connectParameters[] = $dsn->getSocket();
            $connectParameters[] = null;
        } else {
            $connectParameters[] = ($dsn->getTls() ? 'tls://' : '') . $dsn->getHost();
            $connectParameters[] = $dsn->getPort();
        }

        $connectParameters[] = $options['connection_timeout'];
        $connectParameters[] = empty($options['connection_persistent']) ? null : $dsn->getPersistentId();
        $connectParameters[] = 5; // retry interval
        $connectParameters[] = 5; // read timeout
        $connectParameters[] = []; // $context

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

        if (defined('Redis::SERIALIZER_JSON')) {
            $types['json'] = \Redis::SERIALIZER_JSON;
        }

        if (array_key_exists($type, $types)) {
            return $types[$type];
        }

        throw new InvalidConfigurationException(sprintf('%s in not a valid serializer. Valid serializers: %s', $type, implode(', ', array_keys($types))));
    }

    /**
     * Load the correct slave failover for RedisCluster
     *
     * @param string $type
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    private function loadSlaveFailoverType($type)
    {
        $types = [
            'none' => \RedisCluster::FAILOVER_NONE,
            'error' => \RedisCluster::FAILOVER_ERROR,
            'distribute' => \RedisCluster::FAILOVER_DISTRIBUTE,
            'distribute_slaves' => \RedisCluster::FAILOVER_DISTRIBUTE_SLAVES
        ];

        if (array_key_exists($type, $types)) {
            return $types[$type];
        }

        throw new InvalidConfigurationException(sprintf('%s in not a valid slave failover. Valid failovers: %s', $type, implode(', ', array_keys($types))));
    }

    /**
     * @template T of \Redis|\RedisCluster
     * @param T $client
     * @return T
     */
    private function createLoggingProxy(object $client, string $alias): object
    {
        $prefixInterceptors = [];
        $suffixInterceptors = [];
        $classToCopyMethodsFrom = $client instanceof \Redis ? \Redis::class : \RedisCluster::class;

        foreach ((new \ReflectionClass($classToCopyMethodsFrom))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if ($name[0] === '_' || in_array($name, self::CLIENT_ONLY_COMMANDS, true)) {
                continue;
            }

            $prefixInterceptors[$name] = function (
                AccessInterceptorInterface $proxy,
                object $instance,
                string $method
            ) use (&$time, &$event): void {
                $time = microtime(true);

                if ($this->stopwatch) {
                    $event = $this->stopwatch->start($method, 'redis');
                }
            };
            $suffixInterceptors[$name] = function (
                AccessInterceptorInterface $proxy,
                object $instance,
                string $method,
                array $args
            ) use ($alias, &$time, &$event): void {
                $this->logger->logCommand($this->getCommandString($method, array_values($args)), microtime(true) - $time, $alias);

                if ($event) {
                    $event->stop();
                }
            };
        }

        return (new AccessInterceptorScopeLocalizerFactory($this->proxyConfiguration))
            ->createProxy($client, $prefixInterceptors, $suffixInterceptors)
        ;
    }

    /**
     * Returns a string representation of the given command including arguments.
     *
     * @param string $command   A command name
     * @param array  $arguments List of command arguments
     *
     * @return string
     */
    private function getCommandString(string $command, array $arguments)
    {
        $list = [];
        $this->flatten($arguments, $list);

        return trim(strtoupper($command).' '.implode(' ', $list));
    }

    /**
     * Flatten arguments to single dimension array.
     *
     * @param array $arguments An array of command arguments
     * @param array $list      Holder of results
     */
    private function flatten(array $arguments, array &$list)
    {
        foreach ($arguments as $key => $item) {
            if (!is_numeric($key)) {
                $list[] = $key;
            }

            if (is_scalar($item)) {
                $list[] = strval($item);
            } elseif (null === $item) {
                $list[] = '<null>';
            } else {
                $this->flatten($item, $list);
            }
        }
    }
}
