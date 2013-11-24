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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LoggingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('snc_redis.connection_parameters') as $id => $attr) {
            $clientAlias = $attr[0]['clientAlias'];
            $parameterDefinition = $container->getDefinition($id);
            $parameters = $parameterDefinition->getArgument(0);
            if ($parameters['logging']) {
                $optionId = sprintf('snc_redis.client.%s_options', $clientAlias);
                $option = $container->getDefinition($optionId);
                if (1 < count($option->getArguments())) {
                    throw new \RuntimeException('Please check the predis option arguments.');
                }
                $arguments = $option->getArgument(0);

                $connectionFactoryId = sprintf('snc_redis.%s_connectionfactory', $clientAlias);
                $connectionFactoryDef = new Definition($container->getParameter('snc_redis.connection_factory.class'));
                $connectionFactoryDef->setPublic(false);
                $connectionFactoryDef->setScope(ContainerInterface::SCOPE_CONTAINER);
                $connectionFactoryDef->addArgument(new Reference(sprintf('snc_redis.client.%s_profile', $clientAlias)));
                $connectionFactoryDef->addMethodCall('setConnectionWrapperClass', array($container->getParameter('snc_redis.connection_wrapper.class')));
                $connectionFactoryDef->addMethodCall('setLogger', array(new Reference('snc_redis.logger')));
                $container->setDefinition($connectionFactoryId, $connectionFactoryDef);

                $arguments['connections'] = new Reference($connectionFactoryId);
                $option->replaceArgument(0, $arguments);
            }
        }
    }
}
