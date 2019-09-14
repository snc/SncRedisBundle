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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Snc\RedisBundle\Client\Phpredis\Client as PhpredisClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Base Class for command tests
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
abstract class CommandTestCase extends TestCase
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var \Predis\Client|MockObject
     */
    protected $predisClient;

    /**
     * @var PhpRedisClient|MockObject
     */
    protected $phpredisClient;

    public function setUp()
    {
        $this->predisClient = $this->getMockBuilder('\\Predis\\Client')->getMock();
        $this->phpredisClient = $this->getMockBuilder('PhpredisClient')
            ->disableOriginalConstructor()
            ->getMock();

        $kernel = $this->getMockBuilder('\\Symfony\\Component\\HttpKernel\\Kernel')
            ->disableOriginalConstructor()
            ->getMock();
        $kernel->method('getBundles')->willReturn([]);
        $kernel->method('getContainer')->willReturn(new Container());

        $this->kernel = $kernel;
    }

    /**
     * @param array<string, object> $clients
     */
    public function createApplication(array $clients): Application
    {
        $locator = new ServiceLocator(
            array_map(
                function ($service) {
                    return function () use ($service) {
                        return $service;
                    };
                },
                $clients
            )
        );

        $application = new Application($this->kernel);
        $application->add($this->getCommand($locator));

        return $application;
    }

    protected function registerPredisClient(): void
    {
        $this->predisClient = $this->getMockBuilder('\\Predis\\Client')->getMock();

        $this->clientLocator = new ServiceLocator([
            'snc_redis.default' => function() { return $this->predisClient; }
        ]);
    }

    protected function registerPhpredisClient(): void
    {
        $this->phpredisClient = $this->getMockBuilder('\\Snc\\RedisBundle\\Client\\Phpredis\\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientLocator = new ServiceLocator([
            'snc_redis.default' => function() { return $this->phpredisClient; }
        ]);
    }

    /**
     * Method used by the implementation of the command test to return the actual command object
     */
    abstract protected function getCommand(ServiceLocator $locator): Command;
}
