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
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\KernelInterface;
use Predis\Client;

/**
 * Base Class for command tests
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
abstract class CommandTestCase extends TestCase
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Client|MockObject
     */
    protected $predisClient;

    /**
     * @var SymfonyContainerInterface|MockObject
     */
    protected $container;

    /**
     * @var ServiceLocator|MockObject
     */
    protected $clientLocator;

    /**
     * SetUp called before each tests, setting up the environment (application, globally used mocks)
     */
    public function setUp()
    {
        $this->container = $this->createMock(SymfonyContainerInterface::class);
        $this->clientLocator = $this->createMock(ServiceLocator::class);

        /** @var KernelInterface|MockObject $kernel */
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects($this->once())
            ->method('getBundles')
            ->willReturn(array());
        $kernel->expects($this->any())
            ->method('getContainer')
            ->willReturn($this->container);

        $this->predisClient = $this->createMock(Client::class);

        $command = $this->getCommand($this->clientLocator);

        $this->application = new Application($kernel);
        $this->application->add($command);
    }

    protected function registerPredisClient()
    {
        $this->predisClient = $this->createMock(Client::class);

        $this->clientLocator->expects($this->once())
            ->method('get')
            ->willReturn($this->predisClient);
    }

    /**
     * Method used by the implementation of the command test to return the actual command object
     *
     * @param ServiceLocator $clientLocator
     *
     * @return Command The command to be tested
     */
    abstract protected function getCommand(ServiceLocator $clientLocator);
}
