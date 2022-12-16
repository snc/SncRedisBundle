<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\Client\Predis\Connection;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Command\Redis\EXISTS;
use Predis\Command\Redis\SCAN;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use Psr\Log\LoggerInterface;
use Snc\RedisBundle\Client\Predis\Connection\ConnectionWrapper;
use Snc\RedisBundle\Logger\RedisLogger;

class ConnectionWrapperTest extends TestCase
{
    private MockObject $connection;
    private MockObject $logger;
    private RedisLogger $redisLogger;
    private ConnectionWrapper $wrapper;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(NodeConnectionInterface::class);
        $this->connection->method('getParameters')->willReturn(new Parameters(['alias' => 'default']));
        $this->connection->method('executeCommand')->willReturn('');
        $this->redisLogger = new RedisLogger($this->logger = $this->createMock(LoggerInterface::class));
        $this->wrapper     = new ConnectionWrapper($this->connection);
        $this->wrapper->setLogger($this->redisLogger);
    }

    public function testSanitizingArguments(): void
    {
        $command = new SCAN();
        $command->setArguments([null, 'MATCH', 'foo:bar', 'COUNT', 1000]);

        $this->logger->expects($this->once())->method('debug')->with('Executing command "SCAN NULL \'MATCH\' \'foo:bar\' \'COUNT\' 1000"');
        $this->wrapper->executeCommand($command);
    }

    public function testReturnWrappedConnectionAfterException(): void
    {
        $command = new EXISTS();
        $command->setArguments(['key']);

        $this->connection->method('executeCommand')->willThrowException(new ConnectionException($this->wrapper));

        try {
            $this->wrapper->executeCommand($command);
        } catch (ConnectionException $exception) {
            $this->assertSame($this->wrapper, $exception->getConnection());
        }
    }
}
