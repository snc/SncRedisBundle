<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Factory;

use LogicException;
use ProxyManager\Configuration;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use ProxyManager\Proxy\AccessInterceptorInterface;
use Redis;
use RedisCluster;
use ReflectionClass;
use ReflectionMethod;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Stopwatch\Stopwatch;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function defined;
use function implode;
use function in_array;
use function is_a;
use function is_array;
use function is_numeric;
use function is_scalar;
use function microtime;
use function spl_autoload_register;
use function sprintf;
use function strtoupper;
use function strval;
use function trim;

/** @internal */
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
    private RedisLogger $logger;
    private ?Stopwatch $stopwatch              = null;
    private ?Configuration $proxyConfiguration = null;

    public function __construct(RedisLogger $logger, ?Configuration $proxyConfiguration = null, ?Stopwatch $stopwatch = null)
    {
        $this->logger             = $logger;
        $this->stopwatch          = $stopwatch;
        $this->proxyConfiguration = $proxyConfiguration;

        if (!$this->proxyConfiguration) {
            return;
        }

        spl_autoload_register($this->proxyConfiguration->getProxyAutoloader());
    }

    /**
     * @param list<string|list<string>> $dsns    Multiple DSN string
     * @param mixed[]                   $options Options provided in bundle client config
     *
     * @return Redis|RedisCluster
     *
     * @throws InvalidConfigurationException
     * @throws LogicException
     */
    public function create(string $class, array $dsns, array $options, string $alias, bool $loggingEnabled)
    {
        if (!is_a($class, Redis::class, true) && !is_a($class, RedisCluster::class, true)) {
            throw new LogicException(sprintf('The factory can only instantiate \Redis|\RedisCluster classes: "%s" asked', $class));
        }

        // Normalize the DSNs, because using processed environment variables could lead to nested values.
        $dsns = count($dsns) === 1 && is_array($dsns[0]) ? $dsns[0] : $dsns;

        $parsedDsns = array_map(static fn (string $dsn) => new RedisDsn($dsn), $dsns);

        if (is_a($class, Redis::class, true)) {
            if (count($parsedDsns) > 1) {
                throw new LogicException('Cannot have more than 1 dsn with \Redis and \RedisArray is not supported yet.');
            }

            return $this->createClient($parsedDsns[0], $class, $alias, $options, $loggingEnabled);
        }

        return $this->createClusterClient($parsedDsns, $class, $alias, $options, $loggingEnabled);
    }

    /**
     * @param RedisDsn[] $dsns
     * @param mixed[]    $options
     *
     * @throws InvalidConfigurationException
     * @throws LogicException
     */
    private function createClusterClient(array $dsns, string $class, string $alias, array $options, bool $loggingEnabled): RedisCluster
    {
        $client = new $class(
            null,
            array_map(static fn (RedisDsn $dsn) => ($dsn->getTls() ? 'tls://' : '') . $dsn->getHost() . ':' . $dsn->getPort(), $dsns),
            $options['connection_timeout'],
            $options['read_write_timeout'] ?? 0,
            (bool) $options['connection_persistent'],
            $options['parameters']['password'] ?? null,
        );

        if (isset($options['prefix'])) {
            $client->setOption(RedisCluster::OPT_PREFIX, $options['prefix']);
        }

        if (isset($options['serialization'])) {
            $client->setOption(RedisCluster::OPT_SERIALIZER, $this->loadSerializationType($options['serialization']));
        }

        if (isset($options['slave_failover'])) {
            $client->setOption(RedisCluster::OPT_SLAVE_FAILOVER, $this->loadSlaveFailoverType($options['slave_failover']));
        }

        return $loggingEnabled ? $this->createLoggingProxy($client, $alias) : $client;
    }

    /** @param mixed[] $options */
    private function createClient(RedisDsn $dsn, string $class, string $alias, array $options, bool $loggingEnabled): Redis
    {
        $client = new $class();

        if ($loggingEnabled) {
            $client = $this->createLoggingProxy($client, $alias);
        }

        $socket            = $dsn->getSocket();
        $connectParameters = [
            $socket ?? ($dsn->getTls() ? 'tls://' : '') . $dsn->getHost(),
            $dsn->getPort(),
            $options['connection_timeout'],
            empty($options['connection_persistent']) ? null : $dsn->getPersistentId(),
            5, // retry interval
            5, // read timeout
            [], // $context
        ];

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
        if ($db !== null && $db !== '') {
            $client->select($db);
        }

        if (isset($options['prefix'])) {
            $client->setOption(Redis::OPT_PREFIX, $options['prefix']);
        }

        if (isset($options['read_write_timeout'])) {
            $client->setOption(Redis::OPT_READ_TIMEOUT, (float) $options['read_write_timeout']);
        }

        if (isset($options['serialization'])) {
            $client->setOption(Redis::OPT_SERIALIZER, $this->loadSerializationType($options['serialization']));
        }

        return $client;
    }

    /**
     * @return Redis::SERIALIZER_*
     *
     * @throws InvalidConfigurationException
     */
    private function loadSerializationType(string $type): int
    {
        $types = [
            'default' => Redis::SERIALIZER_NONE,
            'json' => Redis::SERIALIZER_JSON,
            'none' => Redis::SERIALIZER_NONE,
            'php' => Redis::SERIALIZER_PHP,
        ];

        if (defined('Redis::SERIALIZER_IGBINARY')) {
            $types['igbinary'] = Redis::SERIALIZER_IGBINARY;
        }

        if (array_key_exists($type, $types)) {
            return $types[$type];
        }

        throw new InvalidConfigurationException(sprintf('%s in not a valid serializer. Valid serializers: %s', $type, implode(', ', array_keys($types))));
    }

    private function loadSlaveFailoverType(string $type): int
    {
        $types = [
            'none' => RedisCluster::FAILOVER_NONE,
            'error' => RedisCluster::FAILOVER_ERROR,
            'distribute' => RedisCluster::FAILOVER_DISTRIBUTE,
            'distribute_slaves' => RedisCluster::FAILOVER_DISTRIBUTE_SLAVES,
        ];

        if (array_key_exists($type, $types)) {
            return $types[$type];
        }

        throw new InvalidConfigurationException(sprintf('%s in not a valid slave failover. Valid failovers: %s', $type, implode(', ', array_keys($types))));
    }

    /**
     * @param T $client
     *
     * @return T
     *
     * @template T of Redis|RedisCluster
     */
    private function createLoggingProxy(object $client, string $alias): object
    {
        $prefixInterceptors     = [];
        $suffixInterceptors     = [];
        $classToCopyMethodsFrom = $client instanceof Redis ? Redis::class : RedisCluster::class;

        foreach ((new ReflectionClass($classToCopyMethodsFrom))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if ($name[0] === '_' || in_array($name, self::CLIENT_ONLY_COMMANDS, true)) {
                continue;
            }

            $prefixInterceptors[$name] = function (
                AccessInterceptorInterface $proxy,
                object $instance,
                string $method,
                array $args
            ) use (
                &$time,
                &$event
            ): void {
                $time = microtime(true);

                if (!$this->stopwatch) {
                    return;
                }

                $event = $this->stopwatch->start($this->getCommandString($method, array_values($args)), 'redis');
            };
            $suffixInterceptors[$name] = function (
                AccessInterceptorInterface $proxy,
                object $instance,
                string $method,
                array $args
            ) use (
                $alias,
                &$time,
                &$event
            ): void {
                $this->logger->logCommand($this->getCommandString($method, array_values($args)), microtime(true) - $time, $alias);

                if (!$event) {
                    return;
                }

                $event->stop();
            };
        }

        return (new AccessInterceptorValueHolderFactory($this->proxyConfiguration))
            ->createProxy($client, $prefixInterceptors, $suffixInterceptors);
    }

    /**
     * Returns a string representation of the given command including arguments.
     *
     * @param mixed[] $arguments List of command arguments
     */
    private function getCommandString(string $command, array $arguments): string
    {
        $list = [];
        $this->flatten($arguments, $list);

        return trim(strtoupper($command) . ' ' . implode(' ', $list));
    }

    /**
     * Flatten arguments to single dimension array.
     *
     * @param mixed[] $arguments An array of command arguments
     * @param mixed[] $list      Holder of results
     */
    private function flatten(array $arguments, array &$list): void
    {
        foreach ($arguments as $key => $item) {
            if (!is_numeric($key)) {
                $list[] = $key;
            }

            if (is_scalar($item)) {
                $list[] = strval($item);
            } elseif ($item === null) {
                $list[] = '<null>';
            } else {
                $this->flatten($item, $list);
            }
        }
    }
}
