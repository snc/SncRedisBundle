<?php

namespace Snc\RedisBundle\Tests\Client\Predis;

use Predis\PredisException;
use Snc\RedisBundle\Client\Predis\Client;

/**
 * ClientTest
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => 'foo.redis.local',
            'port'   => 6379,
        ]);
    }

    /**
     * @covers \Snc\RedisBundle\Client\Phpredis\Client::getCommandString
     */
    public function testPingException()
    {
        if (!method_exists($this, 'expectException'))
            $this->markTestSkipped('This test needs PHPUnit >= 5.2.0');

        $this->expectException(PredisException::class);
        $this->client->ping();
    }

    public function testGetCommandString()
    {
        $this->client->setUnavailable();
        $this->assertTrue(null === $this->client->get('foo:bar'));
        $this->assertTrue(null === $this->client->hget('foo:bar', 'foo'));
        $this->assertTrue(null === $this->client->hgetall('foo:bar'));
        $this->assertTrue(null === $this->client->hmget('foo:bar', []));
    }

    public function testSetCommandString()
    {
        $this->assertTrue('OK'=== $this->client->set('foo', 'bar'));
        $this->assertTrue('OK'=== $this->client->setex('foo', 10,'bar'));
        $this->assertTrue('OK'=== $this->client->psetex('foo', 10,'bar'));
        $this->assertTrue('OK'=== $this->client->hmset('foo', ['a' => 5]));
        $this->assertTrue('OK'=== $this->client->mset(['foo', 'bar']));
    }
}
