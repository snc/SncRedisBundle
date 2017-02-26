<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Tests\Logger;

use Snc\RedisBundle\Logger\RedisLogger;

class RedisLoggerTest extends \PHPUnit_Framework_TestCase
{
    private $logger;
    private $redisLogger;

    private function setUpWithPsrLogger()
    {
        if (!interface_exists('Psr\Log\LoggerInterface')) {
            $this->markTestSkipped('PSR-3 logger package is not installed.');
        }

        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->redisLogger = new RedisLogger($this->logger);
    }

    private function setUpWithHttpKernelLogger()
    {
        if (!interface_exists('Symfony\Component\HttpKernel\Log\LoggerInterface')) {
            $this->markTestSkipped('The Symfony LoggerInterface is not available as Symfony 3+ is installed.');
        }

        $this->logger = $this->getMock('Symfony\Component\HttpKernel\Log\LoggerInterface');
        $this->redisLogger = new RedisLogger($this->logger);
    }

    public function testLogSuccessfulCommand()
    {
        $this->setUpWithPsrLogger();

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Executing command "foo"'));

        $this->redisLogger->logCommand('foo', 10, 'connection');
    }

    public function testLogFailedCommand()
    {
        $this->setUpWithPsrLogger();

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->equalTo('Command "foo" failed (error message)'));

        $this->redisLogger->logCommand('foo', 10, 'connection', 'error message');
    }

    public function testCountLoggedCommands()
    {
        $this->setUpWithPsrLogger();

        $this->logger->expects($this->any())->method('info');

        for ($i = 0; $i < 3; $i++) {
            $this->redisLogger->logCommand('foo'.$i, 10, 'connection');
        }

        $this->assertEquals(3, $this->redisLogger->getNbCommands());
    }

    public function testCommands()
    {
        $this->setUpWithPsrLogger();

        $this->logger->expects($this->any())->method('info');
        $this->logger->expects($this->any())->method('error');

        for ($i = 0; $i < 3; $i++) {
            $this->redisLogger->logCommand('foo'.$i, ($i+1) * 10, 'connection', $i % 2 ? 'error message' : false);
        }

        $this->assertEquals(array(
            array('cmd' => 'foo0', 'executionMS' => 10, 'conn' => 'connection', 'error' => false),
            array('cmd' => 'foo1', 'executionMS' => 20, 'conn' => 'connection', 'error' => 'error message'),
            array('cmd' => 'foo2', 'executionMS' => 30, 'conn' => 'connection', 'error' => false),
        ), $this->redisLogger->getCommands());
    }

    public function testLogSuccessfulCommandWithoutLogger()
    {
        $redisLogger = new RedisLogger();

        $redisLogger->logCommand('foo', 10, 'connection');
    }

    public function testLogFailedCommandWithoutLogger()
    {
        $redisLogger = new RedisLogger();

        $redisLogger->logCommand('foo', 10, 'connection', 'error message');
    }

    public function testCountLoggedCommandsWithoutLogger()
    {
        $redisLogger = new RedisLogger();

        for ($i = 0; $i < 3; $i++) {
            $redisLogger->logCommand('foo'.$i, 10, 'connection');
        }

        $this->assertEquals(3, $redisLogger->getNbCommands());
    }

    public function testCommandsWithoutLogger()
    {
        $redisLogger = new RedisLogger();

        for ($i = 0; $i < 3; $i++) {
            $redisLogger->logCommand('foo'.$i, ($i+1) * 10, 'connection', $i % 2 ? 'error message' : false);
        }

        $this->assertEquals(array(), $redisLogger->getCommands());
    }

    /**
     * @group legacy
     */
    public function testLogSucccessfulCommandWithHttpKernelLogger()
    {
        $this->setUpWithHttpKernelLogger();

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Executing command "foo"'));

        $this->redisLogger->logCommand('foo', 10, 'connection');
    }

    /**
     * @group legacy
     */
    public function testLogFailedCommandWithHttpKernelLogger()
    {
        $this->setUpWithHttpKernelLogger();

        if (interface_exists('Psr\Log\LoggerInterface')) {
            $this->logger
                ->expects($this->once())
                ->method('error')
                ->with($this->equalTo('Command "foo" failed (error message)'));
        } else {
            $this->logger
                ->expects($this->once())
                ->method('err')
                ->with($this->equalTo('Command "foo" failed (error message)'));
        }

        $this->redisLogger->logCommand('foo', 10, 'connection', 'error message');
    }

    /**
     * @gropu legacy
     */
    public function testCommandsWithHttpKernelLogger()
    {
        $this->setUpWithHttpKernelLogger();

        $this->logger->expects($this->any())->method('info');
        $this->logger->expects($this->any())->method('err');
        $this->logger->expects($this->any())->method('error');

        for ($i = 0; $i < 3; $i++) {
            $this->redisLogger->logCommand('foo'.$i, ($i+1) * 10, 'connection', $i % 2 ? 'error message' : false);
        }

        $this->assertEquals(array(
            array('cmd' => 'foo0', 'executionMS' => 10, 'conn' => 'connection', 'error' => false),
            array('cmd' => 'foo1', 'executionMS' => 20, 'conn' => 'connection', 'error' => 'error message'),
            array('cmd' => 'foo2', 'executionMS' => 30, 'conn' => 'connection', 'error' => false),
        ), $this->redisLogger->getCommands());
    }
}
