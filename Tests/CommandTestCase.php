<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Snc\RedisBundle\Client\Phpredis\Client as PhpredisClient;

/**
 * Base Class for command tests
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
abstract class CommandTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Console\Application
     */
    protected $application;

    /**
     * @var \Predis\Client
     */
    protected $predisClient;

    /**
     * @var PhpredisClient
     */
    protected $phpredisClient;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * SetUp called before each tests, setting up the environment (application, globally used mocks)
     */
    public function setUp()
    {
        $this->container = $this->getMock('\\Symfony\\Component\\DependencyInjection\\ContainerInterface');

        $kernel = $this->getMockBuilder('\\Symfony\\Component\\HttpKernel\\Kernel')
            ->disableOriginalConstructor()
            ->getMock();
        $kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue(array()));
        $kernel->expects($this->once())
            ->method('getContainer')
            ->will($this->returnValue($this->container));
        $this->application = new Application($kernel);

        $this->predisClient = $this->getMock('\\Predis\\Client');

        $this->phpredisClient = $this->getMockBuilder('PhpredisClient')
            ->disableOriginalConstructor()
            ->getMock();

        $command = $this->getCommand();
        $this->application->add($command);
        $command->setContainer($this->container);
    }

    protected function registerPredisClient()
    {
        $this->predisClient = $this->getMock('\\Predis\\Client');

        $this->container->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->predisClient));
    }

    protected function registerPhpredisClient()
    {
        $this->phpredisClient = $this->getMockBuilder('\\Snc\\RedisBundle\\Client\\Phpredis\\Client')
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
