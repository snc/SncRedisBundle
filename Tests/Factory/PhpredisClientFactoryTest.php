<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Redis;
use RedisCluster;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use function class_exists;
use function defined;
use function fsockopen;
use function sprintf;

class PhpredisClientFactoryTest extends TestCase
{
    private LoggerInterface $logger;
    private RedisLogger $redisLogger;

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
        $factory = new PhpredisClientFactory($this->redisLogger);

        $client = $factory->create(Redis::class, ['redis://localhost:6379'], ['connection_timeout' => 5], 'default', false);

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
        $factory = new PhpredisClientFactory($this->redisLogger);

        $client = $factory->create(
            RedisCluster::class,
            ['redis://localhost:7000'],
            ['connection_timeout' => 5, 'connection_persistent' => false],
            'phprediscluster',
            false
        );

        $this->assertInstanceOf(RedisCluster::class, $client);
        $this->assertNull($client->getOption(RedisCluster::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(RedisCluster::OPT_SERIALIZER));
        $this->assertSame(0., $client->getOption(RedisCluster::OPT_READ_TIMEOUT));
        $this->assertSame(0, $client->getOption(RedisCluster::OPT_SCAN));
        $this->assertSame(0, $client->getOption(RedisCluster::OPT_SLAVE_FAILOVER));
    }

    public function testCreateFullConfig(): void
    {
        $factory = new PhpredisClientFactory($this->redisLogger);

        $client = $factory->create(
            Redis::class,
            ['redis://localhost:6379'],
            [
                'connection_timeout' => 10,
                'connection_persistent' => 'x',
                'prefix' => 'toto',
                'serialization' => 'php',
                'read_write_timeout' => 4,
                'parameters' => [
                    'database' => 3,
                    'password' => 'sncredis',
                ],
            ],
            'alias_test',
            false
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertSame('toto', $client->getOption(Redis::OPT_PREFIX));
        $this->assertSame(1, $client->getOption(Redis::OPT_SERIALIZER));
        $this->assertSame(4., $client->getOption(Redis::OPT_READ_TIMEOUT));
        $this->assertSame(3, $client->getDBNum());
        $this->assertSame('sncredis', $client->getAuth());
        $this->assertNotNull($client->getPersistentID());
        $this->assertNotFalse($client->getPersistentID());
    }

    public function testDsnConfig(): void
    {
        $this->logger->method('debug')->withConsecutive(
            [$this->stringContains('Executing command "CONNECT localhost 6379 5')],
            ['Executing command "AUTH sncredis"'],
            ['Executing command "SELECT 2"']
        );

        $factory = new PhpredisClientFactory($this->redisLogger);

        $client = $factory->create(
            Redis::class,
            ['redis://redis:sncredis@localhost:6379/2'],
            [
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
                'connection_timeout' => 5,
            ],
            'alias_test',
            true
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertSame(2, $client->getDBNum());
        $this->assertSame('sncredis', $client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    public function testNestedDsnConfig(): void
    {
        $factory = new PhpredisClientFactory($this->redisLogger);

        $client = $factory->create(
            Redis::class,
            [['redis://redis:sncredis@localhost:6379/2']],
            [
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
                'connection_timeout' => 5,
            ],
            'alias_test',
            false
        );

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertSame(2, $client->getDBNum());
        $this->assertSame('sncredis', $client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    /**
     * @dataProvider serializationTypes
     */
    public function testLoadSerializationType(string $serializationType, int $serializer): void
    {
        $factory = new PhpredisClientFactory($this->redisLogger);

        $client = $factory->create(
            Redis::class,
            ['redis://localhost:6379'],
            [
                'serialization' => $serializationType,
                'connection_timeout' => 5,
            ],
            'default',
            false
        );

        self::assertSame($serializer, $client->getOption(Redis::OPT_SERIALIZER));
    }

    public function testLoadSerializationTypeFail(): void
    {
        $factory = new PhpredisClientFactory($this->redisLogger);
        $this->expectException(InvalidConfigurationException::class);

        $factory->create(
            Redis::class,
            ['redis://localhost:6379'],
            [
                'serialization' => 'unknown',
                'connection_timeout' => 5,
            ],
            'default',
            false
        );
    }

    /**
     * @return array<string, Redis::SERIALIZER_*>
     */
    public function serializationTypes(): array
    {
        $serializationTypes = [
            ['default', Redis::SERIALIZER_NONE],
            ['none', Redis::SERIALIZER_NONE],
            ['php', Redis::SERIALIZER_PHP],
        ];

        // \Redis::SERIALIZER_JSON is only available since phpredis 5
        if (defined('Redis::SERIALIZER_JSON')) {
            $serializationTypes[] = ['json', Redis::SERIALIZER_JSON];
        }

        return $serializationTypes;
    }
}
