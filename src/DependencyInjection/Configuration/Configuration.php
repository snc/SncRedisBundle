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

namespace Snc\RedisBundle\DependencyInjection\Configuration;

use Monolog\Handler\RedisHandler;
use Predis\Client;
use Predis\Configuration\Options;
use Predis\Connection\Parameters;
use Redis;
use RedisCluster;
use Relay\Relay;
use Snc\RedisBundle\Client\Predis\Connection\ConnectionFactory;
use Snc\RedisBundle\Client\Predis\Connection\ConnectionWrapper;
use Snc\RedisBundle\DataCollector\RedisDataCollector;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function class_exists;
use function is_iterable;
use function trigger_deprecation;

class Configuration implements ConfigurationInterface
{
    private bool $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('snc_redis');

        $rootNode = $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('class')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('client')->defaultValue(Client::class)->end()
                        ->scalarNode('client_options')->defaultValue(Options::class)->end()
                        ->scalarNode('connection_parameters')->defaultValue(Parameters::class)->end()
                        ->scalarNode('connection_factory')->defaultValue(ConnectionFactory::class)->end()
                        ->scalarNode('connection_wrapper')->defaultValue(ConnectionWrapper::class)->end()
                        ->scalarNode('phpredis_client')->defaultValue(Redis::class)->end()
                        ->scalarNode('relay_client')->defaultValue(Relay::class)->end()
                        ->scalarNode('phpredis_clusterclient')->defaultValue(RedisCluster::class)->end()
                        ->scalarNode('logger')->defaultValue(RedisLogger::class)->end()
                        ->scalarNode('data_collector')->defaultValue(RedisDataCollector::class)->end()
                        ->scalarNode('monolog_handler')->defaultValue(RedisHandler::class)->end()
                    ->end()
                ->end()
            ->end();

        $this->addClientsSection($rootNode);
        $this->addMonologSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds the snc_redis.clients configuration
     */
    private function addClientsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->fixXmlConfig('client')
            ->children()
                ->arrayNode('clients')
                    ->useAttributeAsKey('alias', false)
                    ->beforeNormalization()
                        ->always()
                        ->then(static function ($v) {
                            if (is_iterable($v)) {
                                foreach ($v as $name => &$client) {
                                    if (isset($client['alias'])) {
                                        continue;
                                    }

                                    $client['alias'] = $name;
                                }
                            }

                            return $v;
                        })
                    ->end()
                    ->prototype('array')
                        ->fixXmlConfig('dsn')
                        ->validate()
                            ->ifTrue(static function (array $clientConfig): bool {
                                return $clientConfig['logging'] && $clientConfig['type'] === 'phpredis' && !class_exists(\ProxyManager\Configuration::class);
                            })
                            ->thenInvalid('You must install "ocramius/proxy-manager" or "friendsofphp/proxy-manager-lts" in order to enable logging for phpredis client')
                        ->end()
                        ->children()
                            ->scalarNode('type')->isRequired()->end()
                            ->scalarNode('alias')->isRequired()->end()
                            ->booleanNode('logging')->defaultValue($this->debug)->end()
                            ->arrayNode('dsns')
                                ->isRequired()
                                ->performNoDeepMerging()
                                ->beforeNormalization()
                                    ->ifString()->then(static fn ($v) => (array) $v)->end()
                                ->prototype('variable')->end()
                            ->end()
                            ->arrayNode('options')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('commands')
                                        ->useAttributeAsKey('name')
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->booleanNode('connection_async')->defaultFalse()->end()
                                    ->booleanNode('connection_persistent')->defaultFalse()->end()
                                    ->scalarNode('connection_timeout')->cannotBeEmpty()->defaultValue(5)->end()
                                    ->scalarNode('read_write_timeout')->defaultNull()->end()
                                    ->booleanNode('iterable_multibulk')->defaultFalse()->end()
                                    ->booleanNode('throw_errors')->defaultTrue()->end()
                                    ->scalarNode('serialization')->defaultValue('default')->end()
                                    ->scalarNode('cluster')->defaultNull()->end()
                                    ->scalarNode('prefix')->defaultNull()->end()
                                    ->enumNode('replication')
                                        ->values([true, 'predis', 'sentinel'])
                                        ->beforeNormalization()
                                            ->ifTrue(static fn ($v) => $v === true)
                                            ->then(static function () {
                                                trigger_deprecation(
                                                    'snc/redis-bundle',
                                                    '4.6',
                                                    'Setting true for "clients.options.replication" is deprecated. Use "predis" or "sentinel" instead',
                                                );

                                                return 'predis';
                                            })
                                        ->end()
                                    ->end()
                                    ->scalarNode('service')->defaultNull()->end()
                                    ->enumNode('slave_failover')->values(['none', 'error', 'distribute', 'distribute_slaves'])->end()
                                    ->arrayNode('parameters')
                                        ->canBeUnset()
                                        ->children()
                                            ->scalarNode('database')->defaultNull()->end()
                                            ->scalarNode('username')->defaultNull()->end()
                                            ->scalarNode('password')->defaultNull()->end()
                                            ->booleanNode('logging')->defaultValue($this->debug)->end()
                                            ->variableNode('ssl_context')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds the snc_redis.monolog configuration
     */
    private function addMonologSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('monolog')
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('client')->isRequired()->end()
                        ->scalarNode('key')->isRequired()->end()
                        ->scalarNode('formatter')->end()
                    ->end()
                ->end()
            ->end();
    }
}
