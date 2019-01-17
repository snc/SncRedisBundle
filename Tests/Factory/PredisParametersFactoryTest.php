<?php

namespace Snc\RedisBundle\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\Factory\PredisParametersFactory;
use Predis\Connection\Parameters;

class PredisParametersFactoryTest extends TestCase
{
    public function createDp()
    {
        return array(
            array(
                'redis://z:df577d779b4f724c8c29b5eff5bcc534b732722b9df308a661f1b79014175063d5@ec2-34-321-123-45.us-east-1.compute.amazonaws.com:3210',
                Parameters::class,
                array(
                    'test' => 123,
                    'some' => 'string',
                    'arbitrary' => true,
                    'values' => array(1, 2, 3)
                ),
                array(
                    'test' => 123,
                    'some' => 'string',
                    'arbitrary' => true,
                    'values' => array(1, 2, 3),
                    'scheme' => 'tcp',
                    'host' => 'ec2-34-321-123-45.us-east-1.compute.amazonaws.com',
                    'port' => 3210,
                    'path' => null,
                    'alias' => null,
                    'timeout' => null,
                    'read_write_timeout' => null,
                    'async_connect' => null,
                    'tcp_nodelay' => null,
                    'persistent' => null,
                    'password' => 'df577d779b4f724c8c29b5eff5bcc534b732722b9df308a661f1b79014175063d5',
                    'database' => null,
                ),
            ),
            array(
                'redis://pw@/var/run/redis/redis-1.sock/10',
                Parameters::class,
                array(
                    'test' => 124,
                    'password' => 'toto',
                    'alias' => 'one_alias',
                ),
                array(
                    'test' => 124,
                    'scheme' => 'unix',
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'path' => '/var/run/redis/redis-1.sock',
                    'alias' => 'one_alias',
                    'timeout' => null,
                    'read_write_timeout' => null,
                    'async_connect' => null,
                    'tcp_nodelay' => null,
                    'persistent' => null,
                    'password' => 'pw',
                    'database' => 10,
                ),
            ),
            array(
                'rediss://pw@localhost:6380',
                Parameters::class,
                array(),
                array(
                    'scheme' => 'tls',
                    'host' => 'localhost',
                    'port' => 6380,
                    'password' => 'pw'
                )
            )
        );
    }

    /**
     * @param string $dsn
     * @param string $class
     * @param array  $options
     * @param array  $expectedParameters
     *
     * @dataProvider createDp
     */
    public function testCreate($dsn, $class, $options, $expectedParameters)
    {
        $parameters = PredisParametersFactory::create($options, $class, $dsn);

        $this->assertInstanceOf($class, $parameters);

        foreach ($expectedParameters as $name => $value) {
            $this->assertSame($value, $parameters->{$name}, "Wrong '$name' value");
        }

        // No user can exist within a redis connection.
        $this->assertObjectNotHasAttribute('user', $parameters);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateException()
    {
        PredisParametersFactory::create(array(), \stdClass::class, 'redis://localhost');
    }
}
