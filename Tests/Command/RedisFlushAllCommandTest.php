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
use Snc\RedisBundle\Tests\CommandTestCase;

/**
 * RedisFlushallCommandTest
 */
class RedisFlushallCommandTest extends CommandTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->registerPredisClient();
    }

    public function testWithDefaultClientAndNoInteraction()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.default'));

        $this->predisClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushall'))
            ->will($this->returnValue(true));

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--no-interaction' => true));

        $this->assertRegExp('/All redis databases flushed/', $commandTester->getDisplay());
    }

    public function testClientOption()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.special'));

        $this->predisClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushall'))
            ->will($this->returnValue(true));

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'special', '--no-interaction' => true));

        $this->assertRegExp('/All redis databases flushed/', $commandTester->getDisplay());
    }

    public function testClientOptionWithNotExistingClient()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('snc_redis.notExisting'))
            ->will($this->throwException(new \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException('')));

        $this->predisClient->expects($this->never())
            ->method('__call');

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'notExisting', '--no-interaction' => true));

        $this->assertRegExp('/The client notExisting is not defined/', $commandTester->getDisplay());
    }

    protected function getCommand()
    {
        return new RedisFlushallCommand();
    }
}
