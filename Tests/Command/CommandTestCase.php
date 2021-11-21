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

use PHPUnit\Framework\MockObject\MockObject;
use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Base Class for command tests
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
abstract class CommandTestCase extends TestCase
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Console\Application
     */
    protected $application;

    /**
     * @var \Predis\Client|MockObject
     */
    protected $predisClient;

    /**
     * @var \Redis|MockObject
     */
    protected $phpredisClient;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface|MockObject
     */
    protected $container;

    /**
     * SetUp called before each tests, setting up the environment (application, globally used mocks)
     */
    public function setUp(): void
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        /** @var Kernel|MockObject $kernel */
        $kernel = $this->getMockBuilder(Kernel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue(array()));
        $kernel->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($this->container));
        $this->application = new Application($kernel);

        $this->predisClient = $this->getMockBuilder(Client::class)->getMock();

        $this->phpredisClient = $this->getMockBuilder(\Redis::class)
            ->disableOriginalConstructor()
            ->getMock();

        $command = $this->getCommand();
        $this->application->add($command);
        $command->setClientLocator($this->container);
    }

    protected function registerPredisClient()
    {
        $this->predisClient = $this->getMockBuilder(Client::class)->getMock();

        $this->container->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->predisClient));
    }

    protected function registerPhpredisClient()
    {
        $this->phpredisClient = $this->getMockBuilder(\Redis::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->phpredisClient));
    }

    /**
     * Method used by the implementation of the command test to return the actual command object
     *
     * @return mixed The command to be tested
     */
    abstract protected function getCommand();
}
