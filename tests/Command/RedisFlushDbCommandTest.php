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

namespace Snc\RedisBundle\Tests\Command;

use ArrayIterator;
use Predis\Client;
use Snc\RedisBundle\Command\RedisBaseCommand;
use Snc\RedisBundle\Command\RedisFlushDbCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class RedisFlushDbCommandTest extends CommandTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->registerPredisClient();
    }

    public function testDefaultClientAndNoInteraction(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.default'));

        $node1 = $this->getMockBuilder(Client::class)->getMock();
        $node1->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushdb'))
            ->will($this->returnValue(true));
        $node2 = $this->getMockBuilder(Client::class)->getMock();
        $node2->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushdb'))
            ->will($this->returnValue(true));

        $this->predisClient->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new ArrayIterator([$node1, $node2])));

        $command       = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--no-interaction' => true]);

        $this->assertStringContainsString('redis database flushed', $commandTester->getDisplay());
    }

    public function testClientOption(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.special'));

        $node1 = $this->getMockBuilder(Client::class)->getMock();
        $node1->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushdb'))
            ->will($this->returnValue(true));
        $node2 = $this->getMockBuilder(Client::class)->getMock();
        $node2->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushdb'))
            ->will($this->returnValue(true));

        $this->predisClient->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new ArrayIterator([$node1, $node2])));

        $command       = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--client' => 'special', '--no-interaction' => true]);

        $this->assertStringContainsString('redis database flushed', $commandTester->getDisplay());
    }

    public function testClientOptionWithNotExistingClient(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.notExisting'))
            ->will($this->throwException(new ServiceNotFoundException('')));

        $this->predisClient->expects($this->never())
            ->method('__call');
        $this->predisClient->expects($this->never())
            ->method('getIterator');

        $command       = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--client' => 'notExisting', '--no-interaction' => true]);

        $this->assertStringContainsString('The client "notExisting" is not defined', $commandTester->getDisplay());
    }

    public function testBugFixInPredis(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.default'));

        $this->predisClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushdb'))
            ->will($this->returnValue(true));
        $this->predisClient->method('getIterator')->willReturn(new ArrayIterator([$this->predisClient]));

        $command       = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--no-interaction' => true]);

        $this->assertStringContainsString('redis database flushed', $commandTester->getDisplay());
    }

    protected function getCommand(): RedisBaseCommand
    {
        return new RedisFlushDbCommand();
    }
}
