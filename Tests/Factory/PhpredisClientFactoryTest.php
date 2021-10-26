<?php

namespace Snc\RedisBundle\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Logger\RedisLogger;
use Snc\RedisBundle\Client\Phpredis\Client;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class PhpredisClientFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\Redis::class)) {
            $this->markTestSkipped(sprintf('The %s requires phpredis extension.', __CLASS__));
        } elseif (!@fsockopen('127.0.0.1', 6379)) {
            $this->markTestSkipped(sprintf('The %s requires a redis instance listening on 127.0.0.1:6379.', __CLASS__));
        }

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->redisLogger = new RedisLogger($this->logger);
    }

    public function testCreateMinimalConfig()
    {
        $factory = new PhpredisClientFactory();

        $client = $factory->create(\Redis::class, ['redis://localhost:6379'], array(), 'default');

        $this->assertInstanceOf(\Redis::class, $client);
        $this->assertNull($client->getOption(\Redis::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(\Redis::OPT_SERIALIZER));
        $this->assertSame(0, $client->getDBNum());
        $this->assertNull($client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    public function testCreateMinimalClusterConfig()
    {
        $factory = new PhpredisClientFactory();

        $client = $factory->create(\RedisCluster::class, ['redis://localhost:7000'], [], 'phprediscluster');

        $this->assertInstanceOf(\RedisCluster::class, $client);
        $this->assertNull($client->getOption(\RedisCluster::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(\RedisCluster::OPT_SERIALIZER));
        $this->assertSame(0., $client->getOption(\RedisCluster::OPT_READ_TIMEOUT));
        $this->assertSame(0, $client->getOption(\RedisCluster::OPT_SCAN));
        $this->assertSame(0, $client->getOption(\RedisCluster::OPT_SLAVE_FAILOVER));
    }

    public function testCreateFullConfig()
    {
        $logger = $this->getMockBuilder(RedisLogger::class)->getMock();
        $factory = new PhpredisClientFactory($logger);

        $client = $factory->create(
            Client::class,
            ['redis://localhost:6379'],
            array(
                'connection_timeout' => 10,
                'connection_persistent' => 'x',
                'prefix' => 'toto',
                'serialization' => 'php',
                'read_write_timeout' => 4,
                'parameters' => [
                    'database' => 3,
                    'password' => 'sncredis',
                ],
            ),
            'alias_test'
        );

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame('toto', $client->getOption(\Redis::OPT_PREFIX));
        $this->assertSame(1, $client->getOption(\Redis::OPT_SERIALIZER));
        $this->assertSame(4., $client->getOption(\Redis::OPT_READ_TIMEOUT));
        $this->assertSame(3, $client->getDBNum());
        $this->assertSame('sncredis', $client->getAuth());
        $this->assertNotNull($client->getPersistentID());
        $this->assertNotFalse($client->getPersistentID());

        $refObject = new \ReflectionObject($client);
        $refProperty = $refObject->getProperty('logger');
        $refProperty->setAccessible(true);
        $this->assertSame($logger, $refProperty->getValue($client));
    }

    public function testDsnConfig()
    {
        $factory = new PhpredisClientFactory();

        $client = $factory->create(
            \Redis::class,
            ['redis://redis:sncredis@localhost:6379/2'],
            array(
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
            ),
            'alias_test'
        );

        $this->assertInstanceOf(\Redis::class, $client);
        $this->assertSame(2, $client->getDBNum());
        $this->assertSame('sncredis', $client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    public function testNestedDsnConfig()
    {
        $factory = new PhpredisClientFactory();

        $client = $factory->create(
            \Redis::class,
            [['redis://redis:sncredis@localhost:6379/2']],
            array(
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
            ),
            'alias_test'
        );

        $this->assertInstanceOf(\Redis::class, $client);
        $this->assertSame(2, $client->getDBNum());
        $this->assertSame('sncredis', $client->getAuth());
        $this->assertNull($client->getPersistentID());
    }

    /**
     * @dataProvider serializationTypes
     */
    public function testLoadSerializationType(string $serializationType, int $serializer): void
    {
        $factory = new PhpredisClientFactory();

        $client = $factory->create(
            \Redis::class,
            ['redis://localhost:6379'],
            [
                'serialization' => $serializationType
            ],
            'default'
        );

        self::assertSame($serializer, $client->getOption(\Redis::OPT_SERIALIZER));
    }

    public function testLoadSerializationTypeFail(): void
    {
        $factory = new PhpredisClientFactory();
        $this->expectException(InvalidConfigurationException::class);

        $factory->create(
            \Redis::class,
            ['redis://localhost:6379'],
            [
                'serialization' => 'unknown'
            ],
            'default'
        );
    }

    public function serializationTypes(): array
    {
        $serializationTypes = [
            ['default', \Redis::SERIALIZER_NONE],
            ['none', \Redis::SERIALIZER_NONE],
            ['php', \Redis::SERIALIZER_PHP],
        ];

        // \Redis::SERIALIZER_JSON is only available since phpredis 5
        if (defined('Redis::SERIALIZER_JSON')) {
            $serializationTypes[] = ['json', \Redis::SERIALIZER_JSON];
        }

        return $serializationTypes;
    }
}
