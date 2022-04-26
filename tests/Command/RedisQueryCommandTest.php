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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Snc\RedisBundle\Command\RedisQueryCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

class RedisQueryCommandTest extends TestCase
{
    /** @var Client&MockObject */
    private $predisClient;

    /** @var ContainerInterface&MockObject */
    private $container;

    private CommandTester $tester;

    public function setUp(): void
    {
        $this->container    = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $this->predisClient = $this->getMockBuilder(Client::class)->getMock();

        $cloner = $this->createMock(ClonerInterface::class);
        $cloner->method('cloneVar')->willReturn(new Data([]));

        $this->tester = new CommandTester(
            new RedisQueryCommand($this->container, $this->createMock(DataDumperInterface::class), $cloner)
        );

        $this->container->expects($this->once())->method('get')->will($this->returnValue($this->predisClient));
    }

    public function testWithDefaultClientAndNoInteraction(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('default'));

        $node1 = $this->getMockBuilder(Client::class)->getMock();
        $node1->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushall'))
            ->will($this->returnValue(true));
        $node2 = $this->getMockBuilder(Client::class)->getMock();
        $node2->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushall'))
            ->will($this->returnValue(true));

        $this->predisClient->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new ArrayIterator([$node1, $node2])));

        $this->assertSame(0, $this->tester->execute(['query' => ['flushall']]));
    }

    public function testClientOption(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('special'));

        $node1 = $this->getMockBuilder(Client::class)->getMock();
        $node1->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushall'))
            ->will($this->returnValue(true));
        $node2 = $this->getMockBuilder(Client::class)->getMock();
        $node2->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushall'))
            ->will($this->returnValue(true));

        $this->predisClient->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new ArrayIterator([$node1, $node2])));

        $this->assertSame(0, $this->tester->execute(['query' => ['flushall'], '--client' => 'special']));
    }

    public function testClientOptionWithNotExistingClient(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('notExisting'))
            ->will($this->throwException(new ServiceNotFoundException('')));

        $this->predisClient->expects($this->never())
            ->method('__call');
        $this->predisClient->expects($this->never())
            ->method('getIterator');

        $this->assertSame(1, $this->tester->execute(['query' => ['flushall'], '--client' => 'notExisting']));

        $this->assertStringContainsString('The client "notExisting" is not defined', $this->tester->getDisplay());
    }

    public function testBugFixInPredis(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('default'));

        $this->predisClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushall'))
            ->will($this->returnValue(true));

        $this->predisClient->method('getIterator')->willReturn(new ArrayIterator([$this->predisClient]));

        $this->assertSame(0, $this->tester->execute(['query' => ['flushall']]));
    }
}
