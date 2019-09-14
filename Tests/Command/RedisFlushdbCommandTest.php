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

use Grpc\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Snc\RedisBundle\Command\RedisFlushdbCommand;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * RedisFlushdbCommandTest
 */
class RedisFlushdbCommandTest extends CommandTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->registerPredisClient();
    }

    public function testDefaultClientAndNoInteraction()
    {
        if (!($this->predisClient instanceof \IteratorAggregate)) { // BC for Predis 1.0
            $this->predisClient->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->will($this->returnValue(true));
        } else {
            $node1 = $this->getMockBuilder('\Predis\\Client')->getMock();
            $node1->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->will($this->returnValue(true));
            $node2 = $this->getMockBuilder('\Predis\\Client')->getMock();
            $node2->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->will($this->returnValue(true));

            $connection = $this->getMockBuilder('\Predis\Connection\Aggregate\PredisCluster')->getMock();

            $this->predisClient->expects($this->once())
                ->method('getIterator')
                ->will($this->returnValue(new \ArrayIterator(array($node1, $node2))));
            $this->predisClient->expects($this->once())
                ->method('getConnection')
                ->will($this->returnValue($connection));
        }

        $command = $this->createApplication(['snc_redis.default' => $this->predisClient])->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--no-interaction' => true));

        $this->assertRegExp('/redis database flushed/', $commandTester->getDisplay());
    }

    public function testClientOption()
    {
        if (!($this->predisClient instanceof \IteratorAggregate)) { // BC for Predis 1.0
            $this->predisClient->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->will($this->returnValue(true));
        } else {
            $node1 = $this->getMockBuilder('\Predis\\Client')->getMock();
            $node1->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->will($this->returnValue(true));
            $node2 = $this->getMockBuilder('\Predis\\Client')->getMock();
            $node2->expects($this->once())
                ->method('__call')
                ->with($this->equalTo('flushdb'))
                ->will($this->returnValue(true));

            $connection = $this->getMockBuilder('\Predis\Connection\Aggregate\PredisCluster')->getMock();

            $this->predisClient->expects($this->once())
                ->method('getIterator')
                ->will($this->returnValue(new \ArrayIterator(array($node1, $node2))));
            $this->predisClient->expects($this->once())
                ->method('getConnection')
                ->will($this->returnValue($connection));
        }

        $command = $this->createApplication(['snc_redis.special' => $this->predisClient])->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'special', '--no-interaction' => true));

        $this->assertRegExp('/redis database flushed/', $commandTester->getDisplay());
    }

    public function testClientOptionWithNotExistingClient()
    {
        $command = $this->createApplication([])->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'notExisting', '--no-interaction' => true));

        $this->assertRegExp('/The client "notExisting" is not defined/', $commandTester->getDisplay());
    }

    public function testBugFixInPredis()
    {
        if (!($this->predisClient instanceof \IteratorAggregate)) {
            $this->markTestSkipped('This test for Predis 1.1');
        }

        $connection = $this->getMockBuilder('\Predis\Connection\ConnectionInterface')->getMock();

        $this->predisClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushdb'))
            ->will($this->returnValue(true));
        $this->predisClient->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $command = $this->createApplication(['snc_redis.default' => $this->predisClient])->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--no-interaction' => true));

        $this->assertRegExp('/redis database flushed/', $commandTester->getDisplay());
    }

    protected function getCommand(ServiceLocator $locator): Command
    {
        return new RedisFlushdbCommand($locator);
    }
}
