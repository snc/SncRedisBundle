<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snc\RedisBundle\Command\RedisFlushAllCommand;
use Snc\RedisBundle\DependencyInjection\Compiler\ClientLocatorPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

use function assert;

class ClientLocatorPassTest extends TestCase
{
    public function testThrowRuntimeExceptionWhenNoRedisClientsExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no redis clients found (tag name: snc_redis.client)');

        (new ClientLocatorPass())->process(new ContainerBuilder());
    }

    public function testSetClientLocatorOnTaggedCommands(): void
    {
        $clientDefinition = new Definition('\Predis\Client');
        $clientDefinition->addTag('snc_redis.client');

        $commandDefinition = new Definition(RedisFlushAllCommand::class);
        $commandDefinition->addTag('snc_redis.command');
        $commandDefinition->setPublic(true);

        $container = new ContainerBuilder();
        $container->addDefinitions([
            'snc_redis.predis' => $clientDefinition,
            RedisFlushAllCommand::class => $commandDefinition,
        ]);

        (new ClientLocatorPass())->process($container);

        $container->compile();

        $definition = $container->getDefinition(RedisFlushAllCommand::class);

        $calls = $definition->getMethodCalls();
        $this->assertEquals('setClientLocator', $calls[0][0]);

        $serviceLocatorDefinition = $calls[0][1][0];
        assert($serviceLocatorDefinition instanceof Definition);

        $this->assertEquals(ServiceLocator::class, $serviceLocatorDefinition->getClass());
        $this->assertEquals(
            [
                'snc_redis.predis' => new ServiceClosureArgument(new Reference('snc_redis.predis')),
            ],
            $serviceLocatorDefinition->getArguments()[0]
        );
    }
}
