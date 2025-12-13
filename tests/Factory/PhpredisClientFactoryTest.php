<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\Factory;

use Override;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Redis;
use RedisCluster;
use Relay\Relay;
use SEEC\PhpUnit\Helper\ConsecutiveParams;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Logger\RedisCallInterceptor;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use function class_exists;
use function fsockopen;
use function phpversion;
use function sprintf;
use function version_compare;

/** @psalm-suppress UnusedClass */
final class PhpredisClientFactoryTest extends TestCase
{
    use ConsecutiveParams;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private MockObject $logger;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private RedisLogger $redisLogger;

    #[Override]
    protected function setUp(): void
    {
        if (!class_exists(Redis::class)) {
            $this->markTestSkipped(sprintf('The %s requires phpredis extension.', self::class));
        } elseif (!@fsockopen('127.0.0.1', 6379)) {
            $this->markTestSkipped(sprintf('The %s requires a redis instance listening on 127.0.0.1:6379.', self::class));
        }

        $this->logger      = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->redisLogger = new RedisLogger($this->logger);
    }

    public function testCreateMinimalConfig(): void
    {
        $this->logger->expects($this->never())->method('debug');
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(Redis::class, ['redis://localhost:6379'], ['connection_timeout' => 5], 'default', false);

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertNull($client->getOption(Redis::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(Redis::OPT_SERIALIZER));
        $this->assertSame(0, $client->getDBNum());
        $this->assertNull($client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    /** @requires extension relay */
    public function testCreateRelay(): void
    {
        $this->logger->method('debug')->with(...$this->withConsecutive(
            [$this->stringContains('Executing command "CONNECT localhost 6379 5 <null>')],
        ));

        $client = (new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger)))
            ->create(Relay::class, ['redis://localhost:6379'], ['connection_timeout' => 5], 'default', true);

        $this->assertInstanceOf(Relay::class, $client);
    }

    public function testUnixDsnConfig(): void
    {
        $this->logger->expects($this->never())->method('debug');
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(Redis::class, ['redis:///tmp/redis.sock'], ['connection_timeout' => 5], 'default', false);

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertNull($client->getOption(Redis::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(Redis::OPT_SERIALIZER));
        $this->assertSame(0, $client->getDBNum());
        $this->assertNull($client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    public function testCreateMinimalClusterConfig(): void
    {
        $this->logger->expects($this->never())->method('debug');
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            RedisCluster::class,
            ['redis://localhost:7079'],
            ['connection_timeout' => 5, 'connection_persistent' => false],
            'phprediscluster',
            false,
        );

        $this->assertInstanceOf(RedisCluster::class, $client);
        $this->assertNull($client->getOption(Redis::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(Redis::OPT_SERIALIZER));
        $this->assertSame(0., $client->getOption(Redis::OPT_READ_TIMEOUT));
        $this->assertSame(0, $client->getOption(Redis::OPT_SCAN));
        $this->assertSame(0, $client->getOption(RedisCluster::OPT_SLAVE_FAILOVER));
    }

    public function testCreateMinimalClusterConfigWithAcl(): void
    {
        $this->logger->expects($this->never())->method('debug');
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            RedisCluster::class,
            ['redis://localhost:7079'],
            [
                'connection_timeout' => 5,
                'connection_persistent' => false,
                'parameters' => [
                    'username' => 'snc_redis',
                    'password' => 'snc_password',
                ],
            ],
            'phprediscluster',
            false,
        );

        $this->assertInstanceOf(RedisCluster::class, $client);
        $this->assertNull($client->getOption(Redis::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(Redis::OPT_SERIALIZER));
        $this->assertSame(0., $client->getOption(Redis::OPT_READ_TIMEOUT));
        $this->assertSame(0, $client->getOption(Redis::OPT_SCAN));
        $this->assertSame(0, $client->getOption(RedisCluster::OPT_SLAVE_FAILOVER));
    }

    /**
     * @requires extension relay
     * @testWith ["RedisSentinel", "Redis", null, "sentinelauthdefaultpw"]
     *           ["RedisSentinel", "Redis", "sentinelauth", "sentinelauthpw"]
     *           ["Relay\\Sentinel", "Relay\\Relay", null, "sentinelauthdefaultpw"]
     *           ["Relay\\Sentinel", "Relay\\Relay", "sentinelauth", "sentinelauthpw"]
     */
    public function testCreateSentinelConfig(
        string $sentinelClass,
        string $outputClass,
        ?string $sentinelUser,
        ?string $sentinelPassword
    ): void {
        $this->logger->method('debug')->with(...$this->withConsecutive(
            [$this->stringContains('Executing command "CONNECT 127.0.0.1 6379 5 <null>')],
            ['Executing command "AUTH sncredis"'],
            ['Executing command "GETTIMEOUT"'],
            ['Executing command "GETAUTH"'],
        ));
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            $sentinelClass,
            [
                'redis://undefined@localhost:55555', // unreachable instance
                'redis://sncredis@localhost:26379',
            ],
            [
                'connection_timeout' => 5,
                'connection_persistent' => false,
                'service' => 'mymaster',
                'parameters' => [
                    'sentinel_username' => $sentinelUser,
                    'sentinel_password' => $sentinelPassword,
                ],
            ],
            'phpredissentinel',
            true,
        );

        $this->assertInstanceOf($outputClass, $client);
        $this->assertNull($client->getOption(Redis::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(Redis::OPT_SERIALIZER));
        $this->assertSame(5., $client->getTimeout());
        $this->assertSame('sncredis', $client->getAuth());
    }

    public function testCreateFullConfig(): void
    {
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            ['redis://localhost:6379'],
            [
                'connection_timeout' => 10,
                'connection_persistent' => 'x',
                'prefix' => 'toto',
                'scan' => Redis::SCAN_PREFIX,
                'serialization' => 'php',
                'read_write_timeout' => 4,
                'parameters' => [
                    'database' => 3,
                    'password' => 'sncredis',
                ],
            ],
            'alias_test',
            false,
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertSame('toto', $client->getOption(Redis::OPT_PREFIX));
        $this->assertSame(1, $client->getOption(Redis::OPT_SERIALIZER));
        $this->assertSame(4., $client->getOption(Redis::OPT_READ_TIMEOUT));
        $this->assertSame(3, $client->getDBNum());
        $this->assertSame('sncredis', $client->getAuth());
        $this->assertNotNull($client->getPersistentID());
        $this->assertNotFalse($client->getPersistentID());
        $this->assertEquals(Redis::SCAN_PREFIX, $client->getOption(Redis::OPT_SCAN));
    }

    public function testDsnConfig(): void
    {
        $this->logger->method('debug')->with(...$this->withConsecutive(
            [$this->stringContains('Executing command "CONNECT localhost 6379 5')],
            ['Executing command "AUTH sncredis"'],
            ['Executing command "SELECT 2"'],
            ['Executing command "GETDBNUM"'],
            ['Executing command "GETAUTH"'],
        ));

        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            ['redis://sncredis@localhost:6379/2'],
            [
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
                'connection_timeout' => 5,
            ],
            'alias_test',
            true,
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertSame(2, $client->getDBNum());
        $this->assertSame('sncredis', $client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    public function testDsnConfigWithUsername(): void
    {
        $this->logger->method('debug')->with(...$this->withConsecutive(
            [$this->stringContains('Executing command "CONNECT localhost 7099 5')],
            ['Executing command "AUTH snc_redis snc_password"'],
            ['Executing command "SELECT 0"'],
            ['Executing command "GETDBNUM"'],
            ['Executing command "GETAUTH"'],
        ));

        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            ['redis://snc_redis:snc_password@localhost:7099/0'],
            [
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
                'connection_timeout' => 5,
            ],
            'alias_test',
            true,
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertSame(0, $client->getDBNum());
        $this->assertSame(['snc_redis', 'snc_password'], $client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    public function testNestedDsnConfig(): void
    {
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            [['redis://sncredis@localhost:6379/2']],
            [
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
                'connection_timeout' => 5,
            ],
            'alias_test',
            false,
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertSame(2, $client->getDBNum());
        $this->assertSame('sncredis', $client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    /** @dataProvider serializationTypes */
    public function testLoadSerializationType(string $serializationType, int $serializer): void
    {
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            ['redis://localhost:6379'],
            [
                'serialization' => $serializationType,
                'connection_timeout' => 5,
            ],
            'default',
            false,
        );

        self::assertSame($serializer, $client->getOption(Redis::OPT_SERIALIZER));
    }

    public function testLoadSerializationTypeFail(): void
    {
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));
        $this->expectException(InvalidConfigurationException::class);

        $factory->create(
            Redis::class,
            ['redis://localhost:6379'],
            [
                'serialization' => 'unknown',
                'connection_timeout' => 5,
            ],
            'default',
            false,
        );
    }

    /** @return list<array{0: string, 1: Redis::SERIALIZER_*}> */
    public static function serializationTypes(): array
    {
        return [
            ['default', Redis::SERIALIZER_NONE],
            ['none', Redis::SERIALIZER_NONE],
            ['php', Redis::SERIALIZER_PHP],
            ['json', Redis::SERIALIZER_JSON],
        ];
    }

    public function testMethodsWithVariadicParameters(): void
    {
        $this->logger->method('debug')->with(...$this->withConsecutive(
            [$this->stringContains('Executing command "CONNECT localhost 6379 5')],
            ['Executing command "AUTH sncredis"'],
            ['Executing command "SELECT 2"'],
            ['Executing command "RAWCOMMAND scan fleet cursor 0 limit 10"'],
            ['Executing command "HDEL foo bar"'],
            ['Executing command "UNLINK bar baz"'],
        ));

        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            ['redis://sncredis@localhost:6379/2'],
            [
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
                'connection_timeout' => 5,
            ],
            'alias_test',
            true,
        );

        /** @psalm-suppress TooManyArguments */
        $this->assertFalse($client->rawCommand('scan', 'fleet', 'cursor', '0', 'limit', '10'));
        $client->hDel('foo', 'bar');
        $client->unlink('bar', 'baz');
    }

    public function testMethodWithPassByRefArgument(): void
    {
        $this->logger->method('debug')->with(...$this->withConsecutive(
            [$this->stringContains('Executing command "CONNECT localhost 6379 5')],
            ['Executing command "AUTH sncredis"'],
            ['Executing command "SELECT 2"'],
            ['Executing command "SSCAN set 1 <null> 0"'],
            ['Executing command "SET mykey myvalue <null>"'],
            ['Executing command "SCAN <null> <null> ' . (version_compare(phpversion('redis'), '6', '<') ? '' : '0 ') . '<null>"'],
        ));

        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            ['redis://sncredis@localhost:6379/2'],
            ['connection_timeout' => 5],
            'alias_test',
            true,
        );

        $iterator = 1;
        /** @psalm-suppress TooManyArguments */
        $client->sscan('set', $iterator, null, 0);

        $this->assertSame(0, $iterator);

        $client->set('mykey', 'myvalue');
        /** @psalm-suppress TooFewArguments */
        $this->assertSame(['mykey'], $client->scan($iterator2));
    }

    public function testCreateWithConnectionPersistentTrue(): void
    {
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            ['redis://localhost:6379'],
            [
                'connection_timeout' => 5,
                'connection_persistent' => true,
            ],
            'default',
            false,
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertSame('default', $client->getPersistentID());
    }

    public function testCreateWithConnectionPersistentString(): void
    {
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            ['redis://localhost:6379'],
            [
                'connection_timeout' => 5,
                'connection_persistent' => 'my_custom_persistent_id',
            ],
            'default',
            false,
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertSame('my_custom_persistent_id', $client->getPersistentID());
    }

    public function testCreateWithConnectionPersistentFalse(): void
    {
        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            Redis::class,
            ['redis://localhost:6379'],
            [
                'connection_timeout' => 5,
                'connection_persistent' => false,
            ],
            'default',
            false,
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertNull($client->getPersistentID());
    }
}
