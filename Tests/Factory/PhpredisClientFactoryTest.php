<?php

namespace Snc\RedisBundle\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Logger\RedisLogger;

class PhpredisClientFactoryTest extends TestCase
{
    protected function setUp()
    {
        if (!class_exists('\Redis')) {
            $this->markTestSkipped(sprintf('The %s requires phpredis extension.', __CLASS__));
        }

        if (!@fsockopen('127.0.0.1', 6379)) {
            $this->markTestSkipped(sprintf('The %s requires a redis instance listening on 127.0.0.1:6379.', __CLASS__));
        }

        $this->logger = $this->getMockBuilder('Symfony\Component\HttpKernel\Log\LoggerInterface')->getMock();
        $this->redisLogger = new RedisLogger($this->logger);
    }

    public function testCreateMinimalConfig()
    {
        $factory = new PhpredisClientFactory();

        $client = $factory->create('\Redis', 'redis://localhost:6379', array(), 'default');

        $this->assertInstanceOf('\Redis', $client);
        $this->assertNull($client->getOption(\Redis::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(\Redis::OPT_SERIALIZER));
    }

    public function testCreateFullConfig()
    {
        $logger = $this->getMockBuilder('Snc\RedisBundle\Logger\RedisLogger')->getMock();
        $factory = new PhpredisClientFactory($logger);

        $client = $factory->create(
            '\Snc\RedisBundle\Client\Phpredis\Client',
            'redis://localhost:6379',
            array(
                'connection_timeout' => 10,
                'connection_persistent' => true,
                'prefix' => 'toto',
                'serialization' => 'php',
                'read_write_timeout' => 4,
            ),
            'alias_test'
        );

        $this->assertInstanceOf('\Snc\RedisBundle\Client\Phpredis\Client', $client);
        $this->assertSame('toto', $client->getOption(\Redis::OPT_PREFIX));
        $this->assertSame(1, $client->getOption(\Redis::OPT_SERIALIZER));
        $this->assertSame(4., $client->getOption(\Redis::OPT_READ_TIMEOUT));
        $this->assertAttributeSame($logger, 'logger', $client);
    }
}
