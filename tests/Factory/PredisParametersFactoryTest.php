<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\Factory;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Predis\Connection\Parameters;
use Snc\RedisBundle\Factory\PredisParametersFactory;
use stdClass;

use function sprintf;

use const STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;

class PredisParametersFactoryTest extends TestCase
{
    /** @return array<array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}> */
    public static function createDp(): array
    {
        return [
            [
                'redis://z:df577d779b4f724c8c29b5eff5bcc534b732722b9df308a661f1b79014175063d5@ec2-34-321-123-45.us-east-1.compute.amazonaws.com:3210',
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
                ['replication' => 'predis'],
                [
                    'scheme' => 'tcp',
                    'host' => 'localhost',
                    'port' => 6379,
                    'replication' => 'predis',
                    'password' => null,
                    'weight' => null,
                    'alias' => 'master',
                    'timeout' => null,
                ],
            ],
            [
                'redis://localhost?alias=connection_alias',
                [
                    'replication' => 'predis',
                    'alias' => 'client_alias',
                ],
                [
                    'scheme' => 'tcp',
                    'host' => 'localhost',
                    'port' => 6379,
                    'replication' => 'predis',
                    'password' => null,
                    'weight' => null,
                    'alias' => 'connection_alias',
                    'timeout' => null,
                ],
            ],
            [
                'redis://localhost/0',
                ['persistent' => true],
                [
                    'persistent' => true,
                    'database' => 0,
                ],
            ],
            [
                'redis://localhost',
                ['database' => 11, 'password' => 'pass'],
                [
                    'database' => 11,
                    'password' => 'pass',
                ],
            ],
            'everything in DSN' => [
                'rediss://pw@localhost:6380/0?prefix=foo&alias=connection_alias',
                [],
                $allOptions =
                [
                    'scheme' => 'tls',
                    'host' => 'localhost',
                    'port' => 6380,
                    'password' => 'pw',
                    'database' => 0,
                    'prefix' => 'foo',
                    'alias' => 'connection_alias',
                ],
            ],
            'everything in options' => ['rediss://localhost:6380', $allOptions, $allOptions],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $expectedParameters
     *
     * @dataProvider createDp
     */
    public function testCreate(string $dsn, array $options, array $expectedParameters): void
    {
        $parameters = PredisParametersFactory::create($options, Parameters::class, $dsn);

        foreach ($expectedParameters as $name => $value) {
            $this->assertSame($value, $parameters->{$name}, sprintf("Wrong '%s' value", $name));
        }
    }

    public function testCreateException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @psalm-suppress InvalidArgument */
        PredisParametersFactory::create([], stdClass::class, 'redis://localhost');
    }

    public function testCreateWithTlsVersion(): void
    {
        $parameters = PredisParametersFactory::create([], Parameters::class, 'rediss://localhost:6379?tls_version=1.2');
        $this->assertSame(STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT, $parameters->toArray()['ssl']['crypto_type']);
    }

    public function testCreateMergesSslWithTlsVersion(): void
    {
        $parameters = PredisParametersFactory::create(
            ['ssl' => ['verify_peer' => false]],
            Parameters::class,
            'rediss://localhost:6379?tls_version=1.2',
        );
        $ssl        = $parameters->toArray()['ssl'];
        $this->assertSame(STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT, $ssl['crypto_type']);
        $this->assertFalse($ssl['verify_peer']);
    }

    /**
     * @testWith ["redis://localhost"]
     *           [["redis://localhost"]]
     *           [[["redis://localhost"]]]
     */
    public function testCreateReturnsSingleParameters(string|array $dsn): void
    {
        $result = PredisParametersFactory::create([], Parameters::class, $dsn);
        $this->assertInstanceOf(Parameters::class, $result);
    }

    /**
     * @param array<int, string|array<int, string>> $dsn
     *
     * @testWith [["redis://host1", "redis://host2"]]
     *           [[["redis://host1", "redis://host2"]]]
     */
    public function testCreateReturnsMultipleParameters(array $dsn): void
    {
        $result = PredisParametersFactory::create([], Parameters::class, $dsn);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
}
