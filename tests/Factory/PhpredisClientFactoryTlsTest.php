<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\Factory;

use Override;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Redis;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Logger\RedisCallInterceptor;
use Snc\RedisBundle\Logger\RedisLogger;

use function class_exists;
use function fsockopen;
use function sprintf;

class PhpredisClientFactoryTlsTest extends TestCase
{
    private string $sentinelHost;
    private MockObject $logger;
    private RedisLogger $redisLogger;

    #[Override]
    protected function setUp(): void
    {
        if (!class_exists(Redis::class)) {
            $this->markTestSkipped(sprintf('The %s requires phpredis extension.', self::class));
        }

        $this->sentinelHost = $_ENV['REDIS_SENTINEL_TLS_HOST'] ?? '127.0.0.1';

        if (!@fsockopen($this->sentinelHost, 26380)) {
            $this->markTestSkipped(sprintf('The %s requires a TLS Redis Sentinel listening on %s:26380.', self::class, $this->sentinelHost));
        }

        $this->logger      = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->redisLogger = new RedisLogger($this->logger);
    }

    /**
     * Tests that RedisSentinel connects via TLS when the DSN scheme is rediss://.
     *
     * Before this fix, PhpredisClientFactory passed the sentinel host without the
     * required "tls://" prefix and without an SSL context, causing phpredis to
     * attempt a plain-text handshake on the TLS port and fail with:
     *   SSL routines::wrong version number
     *
     * @testWith ["RedisSentinel", "Redis"]
     */
    public function testCreateSentinelTlsConfig(string $sentinelClass, string $outputClass): void
    {
        $this->logger->expects($this->never())->method('debug');

        $sentinelDsn   = sprintf('rediss://%s:26380', $this->sentinelHost);
        $masterName    = $_ENV['REDIS_SENTINEL_SERVICE'] ?? 'MasterNode';
        $redisPassword = $_ENV['REDIS_PASSWORD'] ?? 'examplepassword';

        $factory = new PhpredisClientFactory(new RedisCallInterceptor($this->redisLogger));

        $client = $factory->create(
            $sentinelClass,
            [$sentinelDsn],
            [
                'connection_timeout'    => 5,
                'connection_persistent' => false,
                'service'               => $masterName,
                'parameters'            => [
                    'password'          => $redisPassword,
                    'sentinel_username' => null,
                    'sentinel_password' => null,
                    'ssl_context'       => [
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ],
                ],
            ],
            'phpredissentineltls',
            false,
        );

        $this->assertInstanceOf($outputClass, $client);
        $this->assertSame($redisPassword, $client->getAuth());
        $this->assertTrue($client->ping('ping'));
    }
}
