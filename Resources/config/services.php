<?php

use ProxyManager\Configuration;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Snc\RedisBundle\Command\RedisFlushallCommand;
use Snc\RedisBundle\Command\RedisFlushdbCommand;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\InlineServiceConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $container = $configurator->services();

    $container->set('snc_redis.logger', '%snc_redis.logger.class%')
        ->tag('monolog.logger', ['channel' => 'snc_redis'])
        ->args([(new ReferenceConfigurator('logger'))->nullOnInvalid()])
    ;

    $container->set('snc_redis.data_collector', '%snc_redis.data_collector.class%')
        ->tag('data_collector', ['id' => 'redis', 'template' => '@SncRedis/Collector/redis.html.twig'])
        ->args([new ReferenceConfigurator('snc_redis.logger')])
    ;

    $container->set('snc_redis.command.flush_all', RedisFlushallCommand::class)
        ->tag('console.command')
        ->tag('snc_redis.command')
    ;

    $container->set('snc_redis.command.flush_db', RedisFlushdbCommand::class)
        ->tag('console.command')
        ->tag('snc_redis.command')
    ;

    $container->set('snc_redis.phpredis_factory', PhpredisClientFactory::class)
        ->arg('$logger', new ReferenceConfigurator('snc_redis.logger'))
        ->arg(
            '$proxyConfiguration',
            new InlineServiceConfigurator(
                (new Definition(Configuration::class))
                    ->addMethodCall('setGeneratorStrategy', [
                        new Definition(
                            FileWriterGeneratorStrategy::class,
                            [new Definition(FileLocator::class, ['%kernel.cache_dir%'])]
                        )
                    ])
                    ->addMethodCall('setProxiesTargetDir', ['%kernel.cache_dir%'])
            )
        )
        ->arg('$stopwatch', (new ReferenceConfigurator('debug.stopwatch'))->nullOnInvalid())
    ;
};
