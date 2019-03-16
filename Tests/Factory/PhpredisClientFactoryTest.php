<?php

namespace Snc\RedisBundle\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Logger\RedisLogger;

class PhpredisClientFactoryTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RedisLogger
     */
    private $redisLogger;

    protected function setUp()
    {
        if (!class_exists(\Redis::class)) {
            $this->markTestSkipped(sprintf('The %s requires phpredis extension.', __CLASS__));
        }

        if (!@fsockopen('127.0.0.1', 6379)) {
            $this->markTestSkipped(sprintf('The %s requires a redis instance listening on 127.0.0.1:6379.', __CLASS__));
        }

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->redisLogger = new RedisLogger($this->logger);

        parent::setUp();
    }

    public function testCreateMinimalConfig()
    {
        $factory = new PhpredisClientFactory();

        $client = $factory->create(\Redis::class, 'redis://localhost:6379', array(), 'default');

        $this->assertInstanceOf(\Redis::class, $client);
        $this->assertNull($client->getOption(\Redis::OPT_PREFIX));
        $this->assertSame(0, $client->getOption(\Redis::OPT_SERIALIZER));
        $this->assertSame(0, $client->getDBNum());
        $this->assertNull($client->getAuth());
    }

    public function testDsnConfig()
    {
        $factory = new PhpredisClientFactory();

        $client = $factory->create(
            '\Redis',
            'redis://redis:pass@localhost:6379/2',
            array(
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
            ),
            'alias_test'
        );

        $this->assertInstanceOf('\Redis', $client);
        $this->assertSame(2, $client->getDBNum());
        $this->assertSame('pass', $client->getAuth());
    }
}
