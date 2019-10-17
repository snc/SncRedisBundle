<?php

namespace Snc\RedisBundle\Tests\Client\Phpredis;

use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\Client\Phpredis\ClientCluster;

/**
 * ClientClusterTest
 */
class ClientClusterTest extends TestCase
{
    protected function setUp()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('This test needs the PHP Redis extension to work');
        } elseif (version_compare(phpversion('redis'), '4.0.0') >= 0) {
            $this->markTestSkipped('This test cannot be executed on Redis extension version ' . phpversion('redis'));
        } elseif (!@fsockopen('127.0.0.1', 7000)) {
            $this->markTestSkipped(sprintf('The %s requires a redis instance listening on 127.0.0.1:7000.', __CLASS__));
        }
    }

    /**
     * @covers \Snc\RedisBundle\Client\Phpredis\ClientCluster::getCommandString
     */
    public function testGetCommandString()
    {
        $method = new \ReflectionMethod(
            ClientCluster::class, 'getCommandString'
        );

        $method->setAccessible(true);

        $seeds = array('127.0.0.1:7000', '127.0.0.1:7001', '127.0.0.1:7002', '127.0.0.1:7003');
        $name = 'foo';
        $arguments = array(array('chuck', 'norris'));

        $this->assertEquals(
            'FOO chuck norris', $method->invoke(new ClientCluster($seeds, array('alias' => 'bar')), $name, $arguments)
        );

        $arguments = array('chuck:norris');

        $this->assertEquals(
            'FOO chuck:norris', $method->invoke(new ClientCluster($seeds, array('alias' => 'bar')), $name, $arguments)
        );

        $arguments = array('chuck:norris fab:pot');

        $this->assertEquals(
            'FOO chuck:norris fab:pot', $method->invoke(new ClientCluster($seeds, array('alias' => 'bar')), $name, $arguments)
        );

        $arguments = array('foo' => 'bar', 'baz' => null);

        $this->assertEquals(
            'FOO foo bar baz <null>', $method->invoke(new ClientCluster($seeds, array('alias' => 'bar')), $name, $arguments)
        );
    }
}
