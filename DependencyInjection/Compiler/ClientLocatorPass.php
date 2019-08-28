<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientLocatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $clients = [];
        foreach ($container->findTaggedServiceIds('snc_redis.client') as $id => $tagAttributes) {
            $clients[$id] = new Reference($id);
        }

        if (!$clients) {
            throw new \RuntimeException('No redis clients found (tag name: snc_redis.client)');
        }

        foreach ($container->findTaggedServiceIds('snc_redis.command') as $id => $tagAttributes) {
            $command = $container->findDefinition($id);
            $command->setArgument(0, ServiceLocatorTagPass::register($container, $clients));
        }
    }
}
