<?php

namespace Snc\RedisBundle\Tests\Factory;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\Factory\PredisParametersFactory;

class PredisParametersFactoryTest extends TestCase
{
    public function createDp()
    {
        return array(
            array(
                'redis://z:df577d779b4f724c8c29b5eff5bcc534b732722b9df308a661f1b79014175063d5@ec2-34-321-123-45.us-east-1.compute.amazonaws.com:3210',
                'Predis\Connection\Parameters',
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
                'Predis\Connection\Parameters',
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
                'Predis\Connection\Parameters',
                array(),
                array(
                    'scheme' => 'tls',
                    'host' => 'localhost',
                    'port' => 6380,
                    'password' => 'pw'
                )
            ),
            array(
                'redis://localhost?alias=master',
                'Predis\Connection\Parameters',
                array('replication' => true),
                array(
                    'scheme' => 'tcp',
                    'host' => 'localhost',
                    'port' => 6379,
                    'replication' => true,
                    'password' => null,
                    'weight' => null,
                    'alias' => 'master',
                    'timeout' => null,
                )
            ),
            array(
                'redis://localhost?alias=connection_alias',
                'Predis\Connection\Parameters',
                array(
                    'replication' => true,
                    'alias' => 'client_alias',
                ),
                array(
                    'scheme' => 'tcp',
                    'host' => 'localhost',
                    'port' => 6379,
                    'replication' => true,
                    'password' => null,
                    'weight' => null,
                    'alias' => 'connection_alias',
                    'timeout' => null,
                )
            ),

            // If replication is disabled, I should be able to specify the password via parameters as well as DSN.
            array(
                'redis://localhost',
                'Predis\Connection\Parameters',
                array(
                    'replication' => false,
                    'parameters' => array(
                        'password' => 'passwordInParameters'
                    ),
                ),
                array(
                    'replication' => false,
                    'password' => 'passwordInParameters',
                )
            ),
            array(
                'redis://localhost',
                'Predis\Connection\Parameters',
                array(
                    'parameters' => array(
                        'password' => 'passwordInParameters'
                    ),
                ),
                array(
                    'password' => 'passwordInParameters',
                )
            ),

            // DSN password should take priority
            array(
                'redis://passwordInDSN@localhost',
                'Predis\Connection\Parameters',
                array(
                    'replication' => false,
                    'parameters' => array(
                        'password' => 'passwordInParameters'
                    ),
                ),
                array(
                    'replication' => false,
                    'password' => 'passwordInDSN',
                )
            ),
            array(
                'redis://passwordInDSN@localhost',
                'Predis\Connection\Parameters',
                array(
                    'parameters' => array(
                        'password' => 'passwordInParameters'
                    ),
                ),
                array(
                    'password' => 'passwordInDSN',
                )
            ),

            // If replication is disabled the password should be in parameters->parameters->password
            array(
                'redis://localhost',
                'Predis\Connection\Parameters',
                array(
                    'replication' => true,
                    'parameters' => array(
                        'password' => 'passwordInParameters'
                    ),
                ),
                array(
                    'replication' => true,
                    'password' => null,
                    'parameters' => array(
                        'password' => 'passwordInParameters'
                    ),
                )
            ),
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

    public function testCreateException()
    {
        $this->expectException(new InvalidArgumentException());
        PredisParametersFactory::create(array(), '\stdClass', 'redis://localhost');
    }
}
