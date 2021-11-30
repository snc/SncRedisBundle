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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Redis;
use Snc\RedisBundle\Command\RedisBaseCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

use function assert;

abstract class CommandTestCase extends TestCase
{
    protected Application $application;

    /** @var Client|MockObject */
    protected $predisClient;

    /** @var Redis|MockObject */
    protected $phpredisClient;

    /** @var ContainerInterface|MockObject */
    protected $container;

    /**
     * SetUp called before each tests, setting up the environment (application, globally used mocks)
     */
    public function setUp(): void
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $kernel = $this->getMockBuilder(Kernel::class)
            ->disableOriginalConstructor()
            ->getMock();
        assert($kernel instanceof KernelInterface || $kernel instanceof MockObject);
        $kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue([]));
        $kernel->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($this->container));
        $this->application = new Application($kernel);

        $this->predisClient = $this->getMockBuilder(Client::class)->getMock();

        $this->phpredisClient = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->getMock();

        $command = $this->getCommand();
        $this->application->add($command);
        $command->setClientLocator($this->container);
    }

    protected function registerPredisClient(): void
    {
        $this->predisClient = $this->getMockBuilder(Client::class)->getMock();

        $this->container->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->predisClient));
    }

    protected function registerPhpredisClient(): void
    {
        $this->phpredisClient = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->phpredisClient));
    }

    abstract protected function getCommand(): RedisBaseCommand;
}
