<?php
declare(strict_types=1);

namespace Snc\RedisBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Snc\RedisBundle\Command\RedisFlushallCommand;
use Snc\RedisBundle\DependencyInjection\Compiler\ClientLocatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\Tests\Compiler\D;

class ClientLocatorPassTest extends TestCase
{
    /** @var ClientLocatorPass */
    private $clientLocatorPass;

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private function getContainerMock() {
        return $this->prophesize(ContainerBuilder::class);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage no redis clients found (tag name: snc_redis.client)
     */
    public function testThrowRuntimeExceptionWhenNoRedisClientsExist()
    {
        $container = $this->getContainerMock();

        $this->clientLocatorPass->process($container->reveal());
    }

    public function testConfigureClientLocatorInContainer()
    {
        $container = new ContainerBuilder();

        $client = new Definition('\Predis\Client');
        $client->addTag('snc_redis.client');

        $locator = new Definition(ServiceLocator::class);

        $container->addDefinitions([
            'snc_redis.default' => $client,
            'snc_redis.client_locator' => $locator
        ]);

        $this->clientLocatorPass->process($container);

        $locator = $container->getDefinition('snc_redis.client_locator');

        $this->assertEquals([['snc_redis.default' => new Reference('snc_redis.default')]], $locator->getArguments());
    }

    protected function setUp()
    {
        $this->clientLocatorPass = new ClientLocatorPass();
    }
}
