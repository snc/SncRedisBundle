<?php

declare(strict_types=1);

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Tests\Logger;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Snc\RedisBundle\Logger\RedisLogger;

use function interface_exists;

class RedisLoggerTest extends TestCase
{
    /** @var MockObject&LoggerInterface */
    private MockObject $logger;
    private RedisLogger $redisLogger;

    private function setUpWithPsrLogger(): void
    {
        if (!interface_exists(LoggerInterface::class)) {
            $this->markTestSkipped('PSR-3 logger package is not installed.');
        }

        $this->logger      = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->redisLogger = new RedisLogger($this->logger);
    }

    public function testLogSuccessfulCommand(): void
    {
        $this->setUpWithPsrLogger();

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Executing command "foo"'));

        $this->redisLogger->logCommand('foo', 10, 'connection');
    }

    public function testLogFailedCommand(): void
    {
        $this->setUpWithPsrLogger();

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->equalTo('Command "foo" failed (error message)'));

        $this->redisLogger->logCommand('foo', 10, 'connection', 'error message');
    }

    public function testCountLoggedCommands(): void
    {
        $this->setUpWithPsrLogger();

        $this->logger->expects($this->any())->method('debug');

        for ($i = 0; $i < 3; $i++) {
            $this->redisLogger->logCommand('foo' . $i, 10, 'connection');
        }

        $this->assertEquals(3, $this->redisLogger->getNbCommands());
    }

    public function testCommands(): void
    {
        $this->setUpWithPsrLogger();

        $this->logger->expects($this->any())->method('debug');
        $this->logger->expects($this->any())->method('error');

        for ($i = 0; $i < 3; $i++) {
            $this->redisLogger->logCommand('foo' . $i, ($i + 1) * 10, 'connection', $i % 2 ? 'error message' : false);
        }

        $this->assertEquals([
            ['cmd' => 'foo0', 'executionMS' => 10, 'conn' => 'connection', 'error' => false],
            ['cmd' => 'foo1', 'executionMS' => 20, 'conn' => 'connection', 'error' => 'error message'],
            ['cmd' => 'foo2', 'executionMS' => 30, 'conn' => 'connection', 'error' => false],
        ], $this->redisLogger->getCommands());
    }

    public function testLogWithoutLogger(): void
    {
        $redisLogger = new RedisLogger();

        $redisLogger->logCommand('foo', 10, 'connection');
        $redisLogger->logCommand('foo', 10, 'connection', 'error message');

        $this->assertEquals([], $redisLogger->getCommands());
        $this->assertEquals(2, $redisLogger->getNbCommands());
    }
}
