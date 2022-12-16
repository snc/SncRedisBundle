<?php

declare(strict_types=1);

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DependencyInjection\Compiler;

use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function count;
use function sprintf;

class LoggingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('snc_redis.connection_parameters') as $id => $attr) {
            $parameterDefinition = $container->getDefinition($id);
            $parameters          = $parameterDefinition->getArgument(0);
            if (!$parameters['logging']) {
                continue;
            }

            $clientAlias = $attr[0]['clientAlias'];
            $option      = $container->getDefinition(sprintf('snc_redis.client.%s_options', $clientAlias));
            if (1 < count($option->getArguments())) {
                throw new RuntimeException('Please check the predis option arguments.');
            }

            $arguments = $option->getArgument(0);

            $connectionFactoryId  = sprintf('snc_redis.%s_connectionfactory', $clientAlias);
            $connectionFactoryDef = new Definition((string) $container->getParameter('snc_redis.connection_factory.class'));
            if ($container->getParameter('kernel.debug')) {
                $connectionFactoryDef->addMethodCall('setStopwatch', [new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE)]);
            }

            $connectionFactoryDef->addMethodCall('setConnectionWrapperClass', [$container->getParameter('snc_redis.connection_wrapper.class')]);
            $connectionFactoryDef->addMethodCall('setLogger', [new Reference('snc_redis.logger')]);
            $container->setDefinition($connectionFactoryId, $connectionFactoryDef);

            $arguments['connections'] = new Reference($connectionFactoryId);
            $option->replaceArgument(0, $arguments);
        }
    }
}
