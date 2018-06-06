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

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

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
        $this->checkForExistingLocatorService($container);

        $clientDefinitions = $this->getRedisClientDefinitions($container);

        $clients = $this->generateServiceClosures($clientDefinitions);

        $clientLocatorDefinition = $this->createClientLocatorDefinition($container, $clients);

        $this->passClientLocatorToSncRedisCommans($container, $clientLocatorDefinition);
    }

    /**
     * @param \Psr\Container\ContainerInterface $container
     */
    private function checkForExistingLocatorService(ContainerInterface $container)
    {
        if ($container->has('snc_redis.client_locator')) {
            throw new \RuntimeException('service id snc_redis.client_locator already assigned');
        }
    }


    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return array
     */
    private function getRedisClientDefinitions(ContainerBuilder $container): array
    {
        $clientDefinitions = $container->findTaggedServiceIds('snc_redis.client');
        if (!$clientDefinitions) {
            throw new \RuntimeException('no redis clients found (tag name: snc_redis.client)');
        }

        return $clientDefinitions;
    }


    /**
     * @param array $clientDefinitions
     *
     * @return array
     */
    private function generateServiceClosures(array $clientDefinitions): array
    {
        $clients = [];
        foreach (array_keys($clientDefinitions) as $key) {
            $clients[$key] = new ServiceClosureArgument(new Reference($key));
        }

        return $clients;
    }


    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param array                                                   $clients
     *
     * @return \Symfony\Component\DependencyInjection\Definition
     */
    private function createClientLocatorDefinition(ContainerBuilder $container, array $clients): Definition
    {
        $clientLocatorDefinition = new Definition(ServiceLocator::class, [$clients]);
        $clientLocatorDefinition->setPrivate(true);

        $container->addDefinitions([$clientLocatorDefinition]);

        return $clientLocatorDefinition;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param \Symfony\Component\DependencyInjection\Definition       $clientLocatorDefinition
     */
    private function passClientLocatorToSncRedisCommans(ContainerBuilder $container, Definition $clientLocatorDefinition)
    {
        $commandDefinitions = $container->findTaggedServiceIds('snc_redis.command');
        foreach (array_keys($commandDefinitions) as $key) {
            $commandDefinition = $container->getDefinition($key);
            $commandDefinition->addMethodCall('setClientLocator', [$clientLocatorDefinition]);
        }
    }
}
