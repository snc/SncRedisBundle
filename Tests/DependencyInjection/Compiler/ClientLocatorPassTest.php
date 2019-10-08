<?php
declare(strict_types=1);

namespace Snc\RedisBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\Command\RedisFlushallCommand;
use Snc\RedisBundle\DependencyInjection\Compiler\ClientLocatorPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ClientLocatorPassTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage no redis clients found (tag name: snc_redis.client)
     */
    public function testThrowRuntimeExceptionWhenNoRedisClientsExist()
    {
        (new ClientLocatorPass())->process(new ContainerBuilder());
    }

    public function testSetClientLocatorOnTaggedCommands()
    {
        $clientDefinition = new Definition('\Predis\Client');
        $clientDefinition->addTag('snc_redis.client');

        $commandDefinition = new Definition(RedisFlushallCommand::class);
        $commandDefinition->addTag('snc_redis.command');
        $commandDefinition->setPublic(true);

        $container = new ContainerBuilder();
        $container->addDefinitions([
            'snc_redis.predis' => $clientDefinition,
            RedisFlushallCommand::class => $commandDefinition,
        ]);

        (new ClientLocatorPass())->process($container);

        $container->compile();

        $definition = $container->getDefinition(RedisFlushallCommand::class);

        $calls = $definition->getMethodCalls();
        $this->assertEquals('setClientLocator', $calls[0][0]);

        /** @var Definition $serviceLocatorDefinition */
        $serviceLocatorDefinition = $calls[0][1][0];

        $this->assertEquals(ServiceLocator::class, $serviceLocatorDefinition->getClass());
        $this->assertEquals(
            [
                'snc_redis.predis' => new ServiceClosureArgument(new Reference('snc_redis.predis'))
            ],
            $serviceLocatorDefinition->getArguments()[0]
        );
    }
}
