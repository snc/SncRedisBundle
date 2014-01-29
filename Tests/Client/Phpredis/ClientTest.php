<?php

namespace Snc\RedisBundle\Tests\Client\Phpredis;

use Snc\RedisBundle\Client\Phpredis\Client;

/**
 * ClientTest
 */
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

        $method->setAccessible(true);

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

        $arguments = array('foo' => 'bar', 'baz' => null);

        $this->assertEquals(
            'FOO foo bar baz <null>', $method->invoke(new \Snc\RedisBundle\Client\Phpredis\Client(array('alias' => 'bar')), $name, $arguments)
        );
    }
}
