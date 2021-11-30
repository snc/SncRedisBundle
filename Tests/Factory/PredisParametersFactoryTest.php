<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\Factory;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Predis\Connection\Parameters;
use Snc\RedisBundle\Factory\PredisParametersFactory;
use stdClass;

use function sprintf;

class PredisParametersFactoryTest extends TestCase
{
    /**
     * @return array<array<string, class-string, array<string, mixed>, array<string, mixed>>>
     */
    public function createDp(): array
    {
        return [
            [
                'redis://z:df577d779b4f724c8c29b5eff5bcc534b732722b9df308a661f1b79014175063d5@ec2-34-321-123-45.us-east-1.compute.amazonaws.com:3210',
                Parameters::class,
                [
                    'test' => 123,
                    'some' => 'string',
                    'arbitrary' => true,
                    'values' => [1, 2, 3],
                ],
                [
                    'test' => 123,
                    'some' => 'string',
                    'arbitrary' => true,
                    'values' => [1, 2, 3],
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
                ],
            ],
            [
                'redis://pw@/var/run/redis/redis-1.sock/10',
                Parameters::class,
                [
                    'test' => 124,
                    'password' => 'toto',
                    'alias' => 'one_alias',
                ],
                [
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
                ],
            ],
            [
                'rediss://pw@localhost:6380',
                Parameters::class,
                [],
                [
                    'scheme' => 'tls',
                    'host' => 'localhost',
                    'port' => 6380,
                    'password' => 'pw',
                ],
            ],
            [
                'redis://localhost?alias=master',
                'Predis\Connection\Parameters',
                ['replication' => true],
                [
                    'scheme' => 'tcp',
                    'host' => 'localhost',
                    'port' => 6379,
                    'replication' => true,
                    'password' => null,
                    'weight' => null,
                    'alias' => 'master',
                    'timeout' => null,
                ],
            ],
            [
                'redis://localhost?alias=connection_alias',
                'Predis\Connection\Parameters',
                [
                    'replication' => true,
                    'alias' => 'client_alias',
                ],
                [
                    'scheme' => 'tcp',
                    'host' => 'localhost',
                    'port' => 6379,
                    'replication' => true,
                    'password' => null,
                    'weight' => null,
                    'alias' => 'connection_alias',
                    'timeout' => null,
                ],
            ],
            [
                'redis://localhost/0',
                'Predis\Connection\Parameters',
                ['persistent' => true],
                [
                    'persistent' => true,
                    'database' => 0,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $expectedParameters
     *
     * @dataProvider createDp
     */
    public function testCreate(string $dsn, string $class, array $options, array $expectedParameters): void
    {
        $parameters = PredisParametersFactory::create($options, $class, $dsn);

        $this->assertInstanceOf($class, $parameters);

        foreach ($expectedParameters as $name => $value) {
            $this->assertSame($value, $parameters->{$name}, sprintf("Wrong '%s' value", $name));
        }

        // No user can exist within a redis connection.
        $this->assertObjectNotHasAttribute('user', $parameters);
    }

    public function testCreateException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PredisParametersFactory::create([], stdClass::class, 'redis://localhost');
    }
}
