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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ClientLocatorPass
 *
 * @package Snc\RedisBundle\DependencyInjection\Compiler
 */
class ClientLocatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $clientDefinitions = $this->getRedisClientDefinitions($container);

        $refMap = [];
        foreach ($clientDefinitions as $id => $clientDefinition) {
            $refMap[$id] = new Reference($id);
        }

        $locator = $container->getDefinition('snc_redis.client_locator');
        $locator->setArgument(0, $refMap);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array<string, Definition>
     */
    private function getRedisClientDefinitions(ContainerBuilder $container): array
    {
        $clientDefinitions = $container->findTaggedServiceIds('snc_redis.client');
        if (!$clientDefinitions) {
            throw new \RuntimeException('no redis clients found (tag name: snc_redis.client)');
        }

        return $clientDefinitions;
    }
}
