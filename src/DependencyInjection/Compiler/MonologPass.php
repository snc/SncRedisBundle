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

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;

use function assert;
use function constant;
use function is_int;
use function strtoupper;

class MonologPass implements CompilerPassInterface
{
    public const SERVICE_ID = 'snc_redis.monolog.handler';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        if (!$container->hasExtension('monolog')) {
            throw new LogicException('SncRedisBundle Monolog integration needs the MonologBundle to be installed');
        }

        $handlerDefinition = $container->getDefinition(self::SERVICE_ID);
        $monologExtension  = $container->getExtension('monolog');
        assert($monologExtension instanceof ConfigurationExtensionInterface);
        $configuration = $monologExtension->getConfiguration([], $container);
        $processor     = new Processor();
        $config        = $processor->processConfiguration($configuration, $container->getExtensionConfig('monolog'));
        foreach ($config['handlers'] as $handler) {
            if (!isset($handler['id']) || $handler['id'] !== self::SERVICE_ID) {
                continue;
            }

            if (isset($handler['level'])) {
                $level = $handler['level'] = is_int($handler['level']) ? $handler['level'] : constant('Monolog\Logger::' . strtoupper($handler['level']));
                $handlerDefinition->addArgument($level);
            }

            if (isset($handler['bubble'])) {
                $handlerDefinition->addArgument($handler['bubble']);
            }

            return;
        }
    }
}
