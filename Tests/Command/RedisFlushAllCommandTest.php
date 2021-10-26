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

use Symfony\Component\Console\Tester\CommandTester;
use Snc\RedisBundle\Command\RedisFlushallCommand;

/**
 * RedisFlushallCommandTest
 */
class RedisFlushallCommandTest extends CommandTestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->registerPredisClient();
    }

    public function testWithDefaultClientAndNoInteraction()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.default'));

        if (!($this->predisClient instanceof \IteratorAggregate)) { // BC for Predis 1.0
            $this->predisClient->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushall'))
                ->will($this->returnValue(true));
        } else {
            $node1 = $this->getMockBuilder('\Predis\Client')->getMock();
            $node1->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushall'))
                ->will($this->returnValue(true));
            $node2 = $this->getMockBuilder('\Predis\Client')->getMock();
            $node2->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushall'))
                ->will($this->returnValue(true));

            $connection = $this->getMockBuilder('\Predis\Connection\Aggregate\PredisCluster')->getMock();

            $this->predisClient->expects($this->once())
                ->method('getIterator')
                ->will($this->returnValue(new \ArrayIterator(array($node1, $node2))));
            $this->predisClient->expects($this->once())
                ->method('getConnection')
                ->will($this->returnValue($connection));
        }

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--no-interaction' => true));

        $this->assertStringContainsString('All redis databases flushed', $commandTester->getDisplay());
    }

    public function testClientOption()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.special'));

        if (!($this->predisClient instanceof \IteratorAggregate)) { // BC for Predis 1.0
            $this->predisClient->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushall'))
                ->will($this->returnValue(true));
        } else {
            $node1 = $this->getMockBuilder('\Predis\Client')->getMock();
            $node1->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushall'))
                ->will($this->returnValue(true));
            $node2 = $this->getMockBuilder('\Predis\Client')->getMock();
            $node2->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushall'))
                ->will($this->returnValue(true));

            $connection = $this->getMockBuilder('\Predis\Connection\Aggregate\PredisCluster')->getMock();

            $this->predisClient->expects($this->once())
                ->method('getIterator')
                ->will($this->returnValue(new \ArrayIterator(array($node1, $node2))));
            $this->predisClient->expects($this->once())
                ->method('getConnection')
                ->will($this->returnValue($connection));
        }

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'special', '--no-interaction' => true));

        $this->assertStringContainsString('All redis databases flushed', $commandTester->getDisplay());
    }

    public function testClientOptionWithNotExistingClient()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.notExisting'))
            ->will($this->throwException(new \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException('')));

        $this->predisClient->expects($this->never())
            ->method('__call');
        $this->predisClient->expects($this->never())
            ->method('getIterator');

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'notExisting', '--no-interaction' => true));

        $this->assertStringContainsString('The client "notExisting" is not defined', $commandTester->getDisplay());
    }

    public function testBugFixInPredis()
    {
        if (!($this->predisClient instanceof \IteratorAggregate)) {
            $this->markTestSkipped('This test for Predis 1.1');
        }

        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.default'));

        $connection = $this->getMockBuilder('\Predis\Connection\ConnectionInterface')->getMock();

        $this->predisClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushall'))
            ->will($this->returnValue(true));
        $this->predisClient->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--no-interaction' => true));

        $this->assertStringContainsString('All redis databases flushed', $commandTester->getDisplay());
    }

    protected function getCommand()
    {
        return new RedisFlushallCommand();
    }
}
