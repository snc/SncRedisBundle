<?php
declare(strict_types=1);

namespace Snc\RedisBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Snc\RedisBundle\Command\RedisFlushallCommand;
use Snc\RedisBundle\DependencyInjection\Compiler\ClientLocatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ClientLocatorPassTest extends TestCase
{
    /** @var ClientLocatorPass */
    private $clientLocatorPass;

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage service id snc_redis.client_locator already assigned
     */
    public function throwRuntimeExceptionWhenServiceIdExists()
    {
        $container = $this->getContainerMock([], [], true);

        $this->clientLocatorPass->process($container->reveal());
    }

    /**
     * @param array $clientServiceDefinitions
     *
     * @param array $commandDefinitions
     * @param bool  $hasLocatorService
     *
     * @return \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private function getContainerMock(
      array $clientServiceDefinitions,
      array $commandDefinitions = [],
      $hasLocatorService = false
    ) {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->has('snc_redis.client_locator')->shouldBeCalled()->willReturn($hasLocatorService);
        if (!$hasLocatorService) {
            $container
              ->findTaggedServiceIds('snc_redis.client')
              ->shouldBeCalled()
              ->willReturn($clientServiceDefinitions);
        }
        if ($commandDefinitions) {
            $container
              ->findTaggedServiceIds('snc_redis.command')
              ->shouldBeCalled()
              ->willReturn($commandDefinitions);
        }

        return $container;
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage no redis clients found (tag name: snc_redis.client)
     */
    public function throwRuntimeExceptionWhenNoRedisClientsExist()
    {
        $container = $this->getContainerMock([]);

        $this->clientLocatorPass->process($container->reveal());
    }

    /**
     * @test
     */
    public function addClientLocatorToContainer()
    {
        $clientDefinition   = new Definition('\Predis\Client');
        $clientDefinitions  = [
          'snc_redis.predis' => $clientDefinition,
        ];
        $commandDefinitions = [
          RedisFlushallCommand::class => new Definition(RedisFlushallCommand::class),
        ];

        $container             = $this->getContainerMock($clientDefinitions, $commandDefinitions);
        $commandDefinitionMock = $this->prophesize(Definition::class);

        $definitionsAssertion = function (array $definitions) use (&$clientLocatorDefinition) {
            self::assertCount(1, $definitions);
            /** @var Definition $definition */
            $definition = $definitions[0];
            self::assertInstanceOf(Definition::class, $definition);
            self::assertTrue($definition->isPrivate());
            self::assertSame(ServiceLocator::class, $definition->getClass());

            $clientLocatorDefinition = $definition;

            return true;
        };
        $container->addDefinitions(Argument::that($definitionsAssertion))->shouldBeCalled();

        $container->getDefinition(RedisFlushallCommand::class)->shouldBeCalled()->willReturn(
          $commandDefinitionMock->reveal()
        );
        $commandDefinitionMock->addMethodCall('setClientLocator', Argument::that($definitionsAssertion))
                              ->shouldBeCalled();


        $this->clientLocatorPass->process($container->reveal());
    }

    protected function setUp()
    {
        $this->clientLocatorPass = new ClientLocatorPass();
    }
}
