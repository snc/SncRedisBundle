<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\Factory;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisException;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Logger\RedisCallInterceptor;
use Snc\RedisBundle\Logger\RedisLogger;

use function class_exists;
use function sprintf;

class PhpredisClientFactoryTlsTest extends TestCase
{
    /**
     * Verifies that a rediss:// DSN causes the sentinel host to be prefixed
     * with tls:// and that the ssl context arg is set.
     */
    public function testSentinelReceivesTlsPrefixAndSslContext(): void
    {
        if (!class_exists(Redis::class)) {
            $this->markTestSkipped(sprintf('The %s requires phpredis extension.', self::class));
        }

        $factory = new class (
            new RedisCallInterceptor(new RedisLogger()),
        ) extends PhpredisClientFactory {
            /** @var array<string, mixed>|null */
            public ?array $capturedArgs = null;

            protected function createSentinelInstance(string $sentinelClass, array $args, bool $useNamedParams): object
            {
                $this->capturedArgs = $args;

                throw new RedisException('spy: short-circuit before real connection');
            }
        };

        try {
            $factory->create(
                'RedisSentinel',
                ['rediss://my-sentinel-host:26380'],
                [
                    'connection_timeout'    => 5,
                    'connection_persistent' => false,
                    'service'               => 'mymaster',
                    'parameters'            => [
                        'password'          => 'secret',
                        'sentinel_username' => null,
                        'sentinel_password' => null,
                        'ssl_context'       => ['verify_peer' => false],
                    ],
                ],
                'phpredissentineltls',
                false,
            );
            $this->fail('Expected RedisException to be thrown');
        } catch (RedisException) {
            // expected — factory catches and retries all DSNs, then throws InvalidArgumentException
        } catch (InvalidArgumentException) {
            // also acceptable — means the factory exhausted the DSN list
        }

        $capturedArgs = $factory->capturedArgs;

        $this->assertNotNull($capturedArgs, 'createSentinelInstance should have been called');
        $this->assertStringStartsWith('tls://', $capturedArgs['host'], 'Sentinel host must use tls:// prefix for rediss:// DSN');
        $this->assertSame('tls://my-sentinel-host', $capturedArgs['host']);
        $this->assertSame(26380, $capturedArgs['port']);
        $this->assertArrayHasKey('ssl', $capturedArgs, 'SSL context must be set for TLS DSN');
        $this->assertSame(['verify_peer' => false], $capturedArgs['ssl']);
    }

    /**
     * Verifies that a plain redis:// DSN does NOT add tls:// prefix.
     */
    public function testSentinelDoesNotUseTlsForPlainDsn(): void
    {
        if (!class_exists(Redis::class)) {
            $this->markTestSkipped(sprintf('The %s requires phpredis extension.', self::class));
        }

        $factory = new class (
            new RedisCallInterceptor(new RedisLogger()),
        ) extends PhpredisClientFactory {
            /** @var array<string, mixed>|null */
            public ?array $capturedArgs = null;

            protected function createSentinelInstance(string $sentinelClass, array $args, bool $useNamedParams): object
            {
                $this->capturedArgs = $args;

                throw new RedisException('spy: short-circuit before real connection');
            }
        };

        try {
            $factory->create(
                'RedisSentinel',
                ['redis://my-sentinel-host:26379'],
                [
                    'connection_timeout'    => 5,
                    'connection_persistent' => false,
                    'service'               => 'mymaster',
                    'parameters'            => [
                        'password'          => 'secret',
                        'sentinel_username' => null,
                        'sentinel_password' => null,
                    ],
                ],
                'phpredissentinel',
                false,
            );
        } catch (RedisException | InvalidArgumentException) {
            // expected
        }

        $capturedArgs = $factory->capturedArgs;

        $this->assertNotNull($capturedArgs, 'createSentinelInstance should have been called');
        $this->assertSame('my-sentinel-host', $capturedArgs['host'], 'Sentinel host must NOT have tls:// prefix for redis:// DSN');
        $this->assertArrayNotHasKey('ssl', $capturedArgs, 'SSL context must NOT be set for plain DSN');
    }
}
