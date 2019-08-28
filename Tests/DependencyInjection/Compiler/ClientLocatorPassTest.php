<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\Command\RedisFlushallCommand;
use Snc\RedisBundle\Command\RedisFlushdbCommand;
use Snc\RedisBundle\DependencyInjection\Compiler\ClientLocatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ClientLocatorPassTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No redis clients found (tag name: snc_redis.client)
     */
    public function testThrowRuntimeExceptionWhenNoRedisClientsExist()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->any())
            ->method('findTaggedServiceIds')
            ->with('snc_redis.client')
            ->willReturn([]);

        (new ClientLocatorPass())->process($container);
    }

    public function testAddClientLocatorToContainer()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->any())
            ->method('findTaggedServiceIds')
            ->willReturnCallback(
                function () {
                    return $this->getData()->current();
                }
            );
        $container->expects($this->any())
            ->method('has')
            ->with($this->stringContains('service_locator.'));
        $container->expects($this->any())
            ->method('hasDefinition')
            ->with($this->stringContains('service_locator.'));
        $container->expects($this->atLeast(2))
            ->method('setDefinition')
            ->with($this->logicalAnd(
                $this->stringContains('service_locator.'),
                $this->anything()
            ));

        $clients = [
            'snc_redis.predis' => new Reference('snc_redis.predis'),
        ];

        $commandDefinitions = [
            RedisFlushallCommand::class => [],
            RedisFlushdbCommand::class => [],
        ];

        foreach ($commandDefinitions as $commandDefinition => $tags) {
            $commandDefinitionMock = $this->createMock(Definition::class);
            $commandDefinitionMock->expects($this->any())
                ->method('addArgument')
                ->withAnyParameters();

            $container
                ->expects($this->atLeastOnce())
                ->method('findDefinition')
                ->willReturn($commandDefinitionMock);
        }

        (new ClientLocatorPass())->process($container);
    }

    private function getData(): \Generator
    {
        yield [
            RedisFlushallCommand::class => [],
            RedisFlushdbCommand::class => [],
        ];
        yield [
            'snc_redis.predis' => ['snc_redis.predis'],
        ];
    }
}
