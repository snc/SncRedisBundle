<?php

declare(strict_types=1);

use ProxyManager\Configuration;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Snc\RedisBundle\Command\RedisQueryCommand;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Logger\RedisCallInterceptor;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\InlineServiceConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $configurator): void {
    $container = $configurator->services();

    $container->set('snc_redis.logger', '%snc_redis.logger.class%')
        ->tag('monolog.logger', ['channel' => 'snc_redis'])
        ->args([(new ReferenceConfigurator('logger'))->nullOnInvalid()]);

    $container->set('snc_redis.data_collector', '%snc_redis.data_collector.class%')
        ->tag('data_collector', ['id' => 'redis', 'template' => '@SncRedis/Collector/redis.html.twig'])
        ->args([new ReferenceConfigurator('snc_redis.logger')]);

    $container->set(RedisQueryCommand::class)
        ->tag('console.command', ['command' => RedisQueryCommand::COMMAND_NAME])
        ->args([
            tagged_locator('snc_redis.client', 'alias'),
            (new ReferenceConfigurator('var_dumper.cli_dumper'))->nullOnInvalid(),
            (new ReferenceConfigurator('var_dumper.cloner'))->nullOnInvalid(),
        ]);

    $container->set(RedisCallInterceptor::class)
        ->class(RedisCallInterceptor::class)
        ->args([
            new ReferenceConfigurator('snc_redis.logger'),
            (new ReferenceConfigurator('debug.stopwatch'))->nullOnInvalid(),
        ]);

    $container->set('snc_redis.phpredis_factory', PhpredisClientFactory::class)
        ->args([
            new ReferenceConfigurator(RedisCallInterceptor::class),
            new InlineServiceConfigurator(
                (new Definition(Configuration::class))
                    ->addMethodCall('setGeneratorStrategy', [
                        new Definition(
                            FileWriterGeneratorStrategy::class,
                            [new Definition(FileLocator::class, ['%kernel.cache_dir%'])],
                        ),
                    ])
                    ->addMethodCall('setProxiesTargetDir', ['%kernel.cache_dir%']),
            ),
        ]);
};
