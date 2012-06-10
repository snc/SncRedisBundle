<?php

namespace Snc\RedisBundle\Tests\Client\Phpredis;

use Snc\RedisBundle\Client\Phpredis\Client;
use Snc\RedisBundle\Logger\RedisLogger;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Snc\RedisBundle\Client\Phpredis\Client::getCommandString
     */
    public function testGetCommandString()
    {
        $method = new \ReflectionMethod(
          '\Snc\RedisBundle\Client\Phpredis\Client', 'getCommandString'
        );

        $method->setAccessible(TRUE);

        $name = 'foo';
        $arguments = array(array('chuck', 'norris'));

        $this->assertEquals(
          'FOO chuck norris', $method->invoke(new \Snc\RedisBundle\Client\Phpredis\Client(array('alias' => 'bar')), $name, $arguments)
        );

        $arguments = array('chuck:norris');

        $this->assertEquals(
          'FOO chuck:norris', $method->invoke(new \Snc\RedisBundle\Client\Phpredis\Client(array('alias' => 'bar')), $name, $arguments)
        );

        $arguments = array('chuck:norris fab:pot');

        $this->assertEquals(
          'FOO chuck:norris fab:pot', $method->invoke(new \Snc\RedisBundle\Client\Phpredis\Client(array('alias' => 'bar')), $name, $arguments)
        );
    }
}
