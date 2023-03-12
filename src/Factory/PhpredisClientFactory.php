<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Factory;

use InvalidArgumentException;
use LogicException;
use ProxyManager\Configuration;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use ProxyManager\Proxy\AccessInterceptorInterface;
use Redis;
use RedisCluster;
use RedisException;
use RedisSentinel;
use ReflectionClass;
use ReflectionMethod;
use Relay\Exception as RelayException;
use Relay\Relay;
use Relay\Sentinel;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function get_class;
use function implode;
use function in_array;
use function is_a;
use function is_array;
use function phpversion;
use function spl_autoload_register;
use function sprintf;
use function var_export;
use function version_compare;

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
    private ?Configuration $proxyConfiguration;
    /** @var callable(object, string, array, ?string): mixed */
    private $interceptor;

    /** @param callable(object, string, array, ?string): mixed $interceptor */
    public function __construct(callable $interceptor, ?Configuration $proxyConfiguration = null)
    {
        $this->interceptor        = $interceptor;
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
     * @return Redis|RedisCluster|Relay
     *
     * @throws InvalidConfigurationException
     * @throws LogicException
     */
    public function create(string $class, array $dsns, array $options, string $alias, bool $loggingEnabled)
    {
        $isRedis    = is_a($class, Redis::class, true);
        $isRelay    = is_a($class, Relay::class, true);
        $isSentinel = is_a($class, RedisSentinel::class, true) || is_a($class, Sentinel::class, true);
        $isCluster  = is_a($class, RedisCluster::class, true);

        if (!$isRedis && !$isRelay && !$isSentinel && !$isCluster) {
            throw new LogicException(sprintf('The factory can only instantiate Redis|Relay\Relay|RedisCluster|RedisSentinel|Relay\Sentinel classes: "%s" asked', $class));
        }

        // Normalize the DSNs, because using processed environment variables could lead to nested values.
        $dsns = count($dsns) === 1 && is_array($dsns[0]) ? $dsns[0] : $dsns;

        $parsedDsns = array_map(static fn (string $dsn) => new RedisDsn($dsn), $dsns);

        if ($isRedis || $isRelay) {
            if (count($parsedDsns) > 1) {
                throw new LogicException('Cannot have more than 1 dsn with \Redis and \RedisArray is not supported yet.');
            }

            return $this->createClient($parsedDsns[0], $class, $alias, $options, $loggingEnabled);
        }

        if ($isSentinel) {
            return $this->createClientFromSentinel($class, $parsedDsns, $alias, $options, $loggingEnabled);
        }

        return $this->createClusterClient($parsedDsns, $class, $alias, $options, $loggingEnabled);
    }

    /**
     * @param class-string                                                                                                    $class
     * @param list<RedisDsn>                                                                                                  $dsns
     * @param array{service: ?string, connection_persistent: ?bool, connection_timeout: ?string, read_write_timeout: ?string} $options
     *
     * @return Redis|Relay
     */
    private function createClientFromSentinel(string $class, array $dsns, string $alias, array $options, bool $loggingEnabled)
    {
        $isRelay              = is_a($class, Sentinel::class, true);
        $sentinelClass        = $isRelay ? Sentinel::class : RedisSentinel::class;
        $masterName           = $options['service'];
        $connectionTimeout    = $options['connection_timeout'] ?? 0;
        $connectionPersistent = $options['connection_persistent'] ? $masterName : null;
        $readTimeout          = $options['read_write_timeout'] ?? 0;

        foreach ($dsns as $dsn) {
            $args = [
                'host' => $dsn->getHost(),
                'port' => (int) $dsn->getPort(),
                'connectTimeout' => $connectionTimeout,
                'persistent' => $connectionPersistent,
                'retryInterval' => 5,
                'readTimeout' => $readTimeout,
            ];
            try {
                if ($isRelay || version_compare(phpversion('redis'), '6.0', '<')) {
                    $sentinel = new $sentinelClass(...array_values($args));
                } else {
                    $sentinel = new $sentinelClass($args);
                }

                $address = $sentinel->getMasterAddrByName($masterName);
            } catch (RedisException | RelayException $e) {
                continue;
            }

            if (!$address) {
                continue;
            }

            return $this->createClient(
                new class ($dsn->__toString(), $address[0], (int) $address[1]) extends RedisDsn {
                    public function __construct(string $dsn, string $host, int $port)
                    {
                        parent::__construct($dsn);
                        $this->host = $host;
                        $this->port = $port;
                    }
                },
                $isRelay ? Relay::class : Redis::class,
                $alias,
                $options,
                $loggingEnabled,
            );
        }

        throw new InvalidArgumentException(
            sprintf(
                'Failed to retrieve master information from sentinel %s and dsn %s.',
                var_export($masterName, true),
                var_export($dsns, true),
            ),
        );
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
            $client->setOption(2, $options['prefix']);
        }

        if (isset($options['serialization'])) {
            $client->setOption(Redis::OPT_SERIALIZER, $this->loadSerializationType($options['serialization']));
        }

        if (isset($options['slave_failover'])) {
            $client->setOption(RedisCluster::OPT_SLAVE_FAILOVER, $this->loadSlaveFailoverType($options['slave_failover']));
        }

        return $loggingEnabled ? $this->createLoggingProxy($client, $alias) : $client;
    }

    /**
     * @param mixed[] $options
     *
     * @return Redis|Relay
     */
    private function createClient(RedisDsn $dsn, string $class, string $alias, array $options, bool $loggingEnabled)
    {
        $client = new $class();

        if ($loggingEnabled) {
            $client = $this->createLoggingProxy($client, $alias);
        }

        $socket  = $dsn->getSocket();
        $context = [];

        if (isset($options['parameters']['ssl_context'])) {
            $context['stream'] = $options['parameters']['ssl_context'];
        }

        $connectParameters = [
            $socket ?? ($dsn->getTls() ? 'tls://' : '') . $dsn->getHost(),
            $dsn->getPort(),
            $options['connection_timeout'],
            empty($options['connection_persistent']) ? null : $dsn->getPersistentId(),
            5, // retry interval
            5, // read timeout
            $context,
        ];

        if (!empty($options['connection_persistent'])) {
            $client->pconnect(...$connectParameters);
        } else {
            $client->connect(...$connectParameters);
        }

        $username = $dsn->getUsername() ?? $options['parameters']['username'] ?? null;
        $password = $dsn->getPassword() ?? $options['parameters']['password'] ?? null;
        if ($username !== null && $password !== null) {
            $client->auth([$username, $password]);
        } elseif ($password !== null) {
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

    /** @throws InvalidConfigurationException */
    private function loadSerializationType(string $type): int
    {
        $types = [
            'default' => 0, // Redis::SERIALIZER_NONE,
            'none' => 0, // Redis::SERIALIZER_NONE,
            'php' => 1, //Redis::SERIALIZER_PHP,
            'igbinary' => 2, //Redis::SERIALIZER_IGBINARY,
            'msgpack' => 3, //Redis::SERIALIZER_MSGPACK,
            'json' => 4, // Redis::SERIALIZER_JSON,
        ];

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
     * @template T of Redis|Relay|RedisCluster
     */
    private function createLoggingProxy(object $client, string $alias): object
    {
        $prefixInterceptors = [];

        foreach ((new ReflectionClass(get_class($client)))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if ($name[0] === '_' || in_array($name, self::CLIENT_ONLY_COMMANDS, true)) {
                continue;
            }

            $variadicParameters = [];
            foreach ($method->getParameters() as $parameter) {
                if (!$parameter->isVariadic()) {
                    continue;
                }

                $variadicParameters[] = $parameter->getName();
            }

            $prefixInterceptors[$name] = function (
                AccessInterceptorInterface $proxy,
                object $instance,
                string $method,
                array $args,
                bool &$returnEarly
            ) use (
                $alias,
                $variadicParameters
            ) {
                $returnEarly = true;
                $listArgs    = [];

                foreach ($args as $argName => &$argValue) {
                    if (!in_array($argName, $variadicParameters, true)) {
                        $listArgs[] = &$argValue;
                        continue;
                    }

                    foreach ($argValue as $variadicParameterValue) {
                        $listArgs[] = $variadicParameterValue;
                    }
                }

                return ($this->interceptor)($instance, $method, $listArgs, $alias);
            };
        }

        return (new AccessInterceptorValueHolderFactory($this->proxyConfiguration))
            ->createProxy($client, $prefixInterceptors);
    }
}
