<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Tests\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Snc\RedisBundle\Command\RedisFlushdbCommand;
use Predis\Client;
use Predis\Connection\Aggregate\PredisCluster;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Predis\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class RedisFlushdbCommandTest extends CommandTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->registerPredisClient();
    }

    public function testDefaultClientAndNoInteraction()
    {
        $this->clientLocator->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.default'));

        if (!($this->predisClient instanceof \IteratorAggregate)) { // BC for Predis 1.0
            $this->predisClient->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->willReturn(true);
        } else {
            $node1 = $this->createMock(Client::class);
            $node1->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->willReturn(true);
            $node2 = $this->createMock(Client::class);
            $node2->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->willReturn(true);

            $connection = $this->createMock(PredisCluster::class);

            $this->predisClient->expects($this->once())
                ->method('getIterator')
                ->willReturn(new \ArrayIterator(array($node1, $node2)));
            $this->predisClient->expects($this->once())
                ->method('getConnection')
                ->willReturn($connection);
        }

        $command = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--no-interaction' => true));

        $this->assertRegExp('/redis database flushed/', $commandTester->getDisplay());
    }

    public function testClientOption()
    {
        $this->clientLocator->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.special'));

        if (!($this->predisClient instanceof \IteratorAggregate)) { // BC for Predis 1.0
            $this->predisClient->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->willReturn(true);
        } else {
            $node1 = $this->createMock(Client::class);
            $node1->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->willReturn(true);
            $node2 = $this->createMock(Client::class);
            $node2->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->willReturn(true);

            $connection = $this->createMock(PredisCluster::class);

            $this->predisClient->expects($this->once())
                ->method('getIterator')
                ->willReturn(new \ArrayIterator(array($node1, $node2)));
            $this->predisClient->expects($this->once())
                ->method('getConnection')
                ->willReturn($connection);
        }

        $command = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'special', '--no-interaction' => true));

        $this->assertRegExp('/redis database flushed/', $commandTester->getDisplay());
    }

    public function testClientOptionWithNotExistingClient()
    {
        $this->clientLocator->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.notExisting'))
            ->willThrowException(new ServiceNotFoundException(''));

        $this->predisClient->expects($this->never())
            ->method('__call');
        $this->predisClient->expects($this->never())
            ->method('getIterator');

        $command = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'notExisting', '--no-interaction' => true));

        $this->assertRegExp('/The client "notExisting" is not defined/', $commandTester->getDisplay());
    }

    public function testBugFixInPredis()
    {
        if (!$this->predisClient instanceof \IteratorAggregate) {
            $this->markTestSkipped('This test for Predis 1.1');
        }

        $this->clientLocator->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.default'));

        $connection = $this->createMock(ConnectionInterface::class);

        $this->predisClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushdb'))
            ->willReturn(true);
        $this->predisClient->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $command = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--no-interaction' => true));

        $this->assertRegExp('/redis database flushed/', $commandTester->getDisplay());
    }

    protected function getCommand(ServiceLocator $clientLocator)
    {
        return new RedisFlushdbCommand($clientLocator);
    }
}
