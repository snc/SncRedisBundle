<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Tests\Session\Storage\Handler;

use Snc\RedisBundle\Session\Storage\Handler\RedisSessionHandler;

/**
 * RedisSessionHandlerTest
 */
class RedisSessionHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $redis;

    protected function setUp()
    {
        $this->redis = $this->getMock('Predis\Client', array('get', 'set', 'setex', 'del'));
    }

    protected function tearDown()
    {
        unset($this->redis);
    }

    public function testSessionReading()
    {
        $this->redis
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('session:_symfony'))
        ;

        $handler = new RedisSessionHandler($this->redis, array(), 'session');
        $handler->read('_symfony');
    }

    public function testDeletingSessionData()
    {
        $this->redis
            ->expects($this->once())
            ->method('del')
            ->with($this->equalTo('session:_symfony'))
        ;

        $handler = new RedisSessionHandler($this->redis, array(), 'session');
        $handler->destroy('_symfony');
    }

    public function testWritingSessionDataWithNoExpiration()
    {
        $this->redis
            ->expects($this->once())
            ->method('set')
            ->with($this->equalTo('session:_symfony'), $this->equalTo('some data'))
        ;

        $handler = new RedisSessionHandler($this->redis, array(), 'session');
        $handler->write('_symfony', 'some data');
    }

    public function testWritingSessionDataWithExpiration()
    {
        $this->redis
            ->expects($this->exactly(3))
            ->method('setex')
            ->with($this->equalTo('session:_symfony'), $this->equalTo(10), $this->equalTo('some data'))
        ;

        // Expiration is set by cookie_lifetime option
        $handler = new RedisSessionHandler($this->redis, array('cookie_lifetime' => 10), 'session');
        $handler->write('_symfony', 'some data');

        // Expiration is set with the TTL attribute
        $handler = new RedisSessionHandler($this->redis, array(), 'session');
        $handler->setTtl(10);
        $handler->write('_symfony', 'some data');

        // TTL attribute overrides cookie_lifetime option
        $handler = new RedisSessionHandler($this->redis, array('cookie_lifetime' => 20), 'session');
        $handler->setTtl(10);
        $handler->write('_symfony', 'some data');
    }
}
