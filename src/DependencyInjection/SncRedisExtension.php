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

namespace Snc\RedisBundle\DependencyInjection;

use InvalidArgumentException;
use LogicException;
use RedisSentinel;
use Snc\RedisBundle\DependencyInjection\Configuration\Configuration;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisEnvDsn;
use Snc\RedisBundle\Factory\PredisParametersFactory;
use Snc\RedisBundle\Logger\RedisCallInterceptor;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function array_map;
use function assert;
use function class_exists;
use function count;
use function sprintf;

class SncRedisExtension extends Extension
{
    /** @param mixed[] $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        $mainConfig = $this->getConfiguration($configs, $container);
        $config     = $this->processConfiguration($mainConfig, $configs);

        $phpredisFactoryDefinition = $container->getDefinition('snc_redis.phpredis_factory');

        if (!$container->getParameter('kernel.debug')) {
            $container->getDefinition(RedisCallInterceptor::class)->replaceArgument(1, null);
        }

        if (!class_exists(\ProxyManager\Configuration::class)) {
            $phpredisFactoryDefinition->replaceArgument(1, null);
        }

        foreach ($config['class'] as $name => $class) {
            $container->setParameter(sprintf('snc_redis.%s.class', $name), $class);
        }

        foreach ($config['clients'] as $client) {
            $this->loadClient($client, $container);
        }

        if (!isset($config['monolog'])) {
            return;
        }

        if (!empty($config['clients'][$config['monolog']['client']]['logging'])) {
            throw new InvalidConfigurationException(sprintf('You have to disable logging for the client "%s" that you have configured under "snc_redis.monolog.client"', $config['monolog']['client']));
        }

        $this->loadMonolog($config, $container);
    }

    public function getNamespace(): string
    {
        return 'http://symfony.com/schema/dic/redis';
    }

    public function getXsdValidationBasePath(): string
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    /** @param array{dsns: array<mixed>, type: string} $client */
    private function loadClient(array $client, ContainerBuilder $container): void
    {
        $dsnResolver = static function ($dsn) use ($container) {
            $usedEnvs = null;
            $container->resolveEnvPlaceholders($dsn, null, $usedEnvs);

            if ($usedEnvs) {
                return new RedisEnvDsn($dsn);
            }

            $parsedDsn = new RedisDsn($dsn);

            if ($parsedDsn->isValid()) {
                return $parsedDsn;
            }

            throw new InvalidArgumentException(sprintf('Given Redis DSN "%s" is invalid.', $dsn));
        };

        $client['dsns'] = array_map($dsnResolver, $client['dsns']);
        $client['type'] = $container->resolveEnvPlaceholders($client['type'], true);

        switch ($client['type']) {
            case 'predis':
                $this->loadPredisClient($client, $container);
                break;
            case 'phpredis':
            case 'relay':
                $this->loadPhpredisClient($client, $container);
                break;
            default:
                throw new InvalidArgumentException(sprintf('The redis client type %s is invalid.', $client['type']));
        }
    }

    /** @param mixed[] $client */
    private function loadPredisClient(array $client, ContainerBuilder $container): void
    {
        if ($client['options']['cluster'] === null) {
            unset($client['options']['cluster']);
        } else {
            unset($client['options']['replication']);
        }

        // predis connection parameters have been renamed in v0.8
        $client['options']['async_connect'] = $client['options']['connection_async'];
        $client['options']['timeout']       = $client['options']['connection_timeout'];
        $client['options']['persistent']    = $client['options']['connection_persistent'];
        $client['options']['exceptions']    = $client['options']['throw_errors'];
        // fix ssl configuration key name
        $client['options']['ssl'] = $client['options']['parameters']['ssl_context'] ?? [];
        unset($client['options']['connection_async']);
        unset($client['options']['connection_timeout']);
        unset($client['options']['connection_persistent']);
        unset($client['options']['throw_errors']);
        unset($client['options']['parameters']['ssl_context']);

        $connectionAliases = [];
        $connectionCount   = count($client['dsns']);

        foreach ($client['dsns'] as $i => $dsn) {
            assert($dsn instanceof RedisEnvDsn || $dsn instanceof RedisDsn);
            $connectionAlias = $dsn instanceof RedisDsn ? $dsn->getAlias() : null;
            if (!$connectionAlias) {
                $connectionAlias = $connectionCount === 1 ? $client['alias'] : $client['alias'] . ($i + 1);
            }

            $connectionAliases[] = $connectionAlias;

            $connection            = $client['options'];
            $connection['logging'] = $client['logging'];
            $connection['alias']   = $connectionAlias;

            $this->loadPredisConnectionParameters($client['alias'], $connection, $container, $dsn);
        }

        $optionId  = sprintf('snc_redis.client.%s_options', $client['alias']);
        $optionDef = new Definition((string) $container->getParameter('snc_redis.client_options.class'));
        $optionDef->addArgument($client['options']);
        $container->setDefinition($optionId, $optionDef);
        $clientDef = new Definition((string) $container->getParameter('snc_redis.client.class'));
        $clientDef->addTag('snc_redis.client', ['alias' => $client['alias']]);
        if ($connectionCount === 1 && !isset($client['options']['cluster']) && !isset($client['options']['replication'])) {
            $clientDef->addArgument(new Reference(sprintf('snc_redis.connection.%s_parameters.%s', $connectionAliases[0], $client['alias'])));
        } else {
            $connections = [];
            foreach ($connectionAliases as $alias) {
                $connections[] = new Reference(sprintf('snc_redis.connection.%s_parameters.%s', $alias, $client['alias']));
            }

            $clientDef->addArgument($connections);
        }

        $clientDef->addArgument(new Reference($optionId));
        $container->setDefinition(sprintf('snc_redis.%s', $client['alias']), $clientDef);
    }

    /**
     * @param mixed[]              $options
     * @param RedisEnvDsn|RedisDsn $dsn
     */
    private function loadPredisConnectionParameters(string $clientAlias, array $options, ContainerBuilder $container, object $dsn): void
    {
        $parametersClass = (string) $container->getParameter('snc_redis.connection_parameters.class');
        $parameterId     = sprintf('snc_redis.connection.%s_parameters.%s', $options['alias'], $clientAlias);

        $parameterDef = new Definition($parametersClass);
        $parameterDef->setFactory([PredisParametersFactory::class, 'create']);
        $parameterDef->addArgument($options);
        $parameterDef->addArgument($parametersClass);
        $parameterDef->addArgument((string) $dsn);
        $parameterDef->addTag('snc_redis.connection_parameters', ['clientAlias' => $clientAlias]);
        $container->setDefinition($parameterId, $parameterDef);
    }

    /** @param mixed[] $options A client configuration */
    private function loadPhpredisClient(array $options, ContainerBuilder $container): void
    {
        $connectionCount   = count($options['dsns']);
        $hasClusterOption  = $options['options']['cluster'] !== null;
        $hasSentinelOption = isset($options['options']['replication']);

        if ($connectionCount > 1 && !$hasClusterOption && !$hasSentinelOption) {
            throw new LogicException('Use options "cluster" or "sentinel" to enable support for multi DSN instances.');
        }

        if ($hasClusterOption && $hasSentinelOption) {
            throw new LogicException('You cannot have both cluster and sentinel enabled for same redis connection');
        }

        $phpredisClientClass = (string) $container->getParameter(
            sprintf('snc_redis.%s_%sclient.class', $options['type'], ($hasClusterOption ? 'cluster' : '')),
        );

        unset($options['options']['commands']);

        $phpredisDef = new Definition($phpredisClientClass, [
            $hasSentinelOption ? RedisSentinel::class : $phpredisClientClass,
            array_map('strval', $options['dsns']),
            $options['options'],
            $options['alias'],
            $options['logging'],
        ]);
        $phpredisDef->setFactory([new Reference('snc_redis.phpredis_factory'), 'create']);
        $phpredisDef->addTag('snc_redis.client', ['alias' => $options['alias']]);
        $phpredisDef->setLazy(true);

        $container->setDefinition(sprintf('snc_redis.%s', $options['alias']), $phpredisDef);
    }

    /** @param mixed[] $config */
    private function loadMonolog(array $config, ContainerBuilder $container): void
    {
        $ref = new Reference(sprintf('snc_redis.%s', $config['monolog']['client']));

        $def = new Definition((string) $container->getParameter('snc_redis.monolog_handler.class'), [
            $ref,
            $config['monolog']['key'],
        ]);

        if (!empty($config['monolog']['formatter'])) {
            $def->addMethodCall('setFormatter', [new Reference($config['monolog']['formatter'])]);
        }

        $container->setDefinition('snc_redis.monolog.handler', $def);
    }

    /** @param mixed[] $config */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration((bool) $container->getParameter('kernel.debug'));
    }
}
