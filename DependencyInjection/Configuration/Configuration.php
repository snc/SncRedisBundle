<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DependencyInjection\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    private $debug;

    public function __construct($debug)
    {
        $this->debug = (Boolean) $debug;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('snc_redis');

        $rootNode = $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('class')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('client')->defaultValue('Predis\Client')->end()
                        ->scalarNode('client_options')->defaultValue('Predis\Configuration\Options')->end()
                        ->scalarNode('connection_parameters')->defaultValue('Predis\Connection\Parameters')->end()
                        ->scalarNode('connection_factory')->defaultValue('Snc\RedisBundle\Client\Predis\Connection\ConnectionFactory')->end()
                        ->scalarNode('connection_wrapper')->defaultValue('Snc\RedisBundle\Client\Predis\Connection\ConnectionWrapper')->end()
                        ->scalarNode('phpredis_client')->defaultValue('Redis')->end()
                        ->scalarNode('phpredis_clusterclient')->defaultValue('RedisCluster')->end()
                        ->scalarNode('logger')->defaultValue('Snc\RedisBundle\Logger\RedisLogger')->end()
                        ->scalarNode('data_collector')->defaultValue('Snc\RedisBundle\DataCollector\RedisDataCollector')->end()
                        ->scalarNode('monolog_handler')->defaultValue('Monolog\Handler\RedisHandler')->end()
                    ->end()
                ->end()
            ->end();

        $this->addClientsSection($rootNode);
        $this->addMonologSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds the snc_redis.clients configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addClientsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('client')
            ->children()
                ->arrayNode('clients')
                    ->useAttributeAsKey('alias', false)
                    ->beforeNormalization()
                        ->always()
                        ->then(function ($v) {
                            if (is_iterable($v)) {
                                foreach ($v as $name => &$client) {
                                    if (!isset($client['alias'])) {
                                        $client['alias'] = $name;
                                    }
                                }
                            }

                            return $v;
                        })
                    ->end()
                    ->prototype('array')
                        ->fixXmlConfig('dsn')
                        ->validate()
                            ->ifTrue(function(array $clientConfig): bool {
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
                                    ->ifString()->then(function ($v) {
                                        return (array) $v;
                                    })
                                ->end()
                                ->prototype('variable')->end()
                            ->end()
                            ->arrayNode('options')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('connection_async')->defaultFalse()->end()
                                    ->booleanNode('connection_persistent')->defaultFalse()->end()
                                    ->scalarNode('connection_timeout')->defaultValue(5)->end()
                                    ->scalarNode('read_write_timeout')->defaultNull()->end()
                                    ->booleanNode('iterable_multibulk')->defaultFalse()->end()
                                    ->booleanNode('throw_errors')->defaultTrue()->end()
                                    ->scalarNode('serialization')->defaultValue('default')->end()
                                    ->scalarNode('profile')->defaultValue('default')->end()
                                    ->scalarNode('cluster')->defaultNull()->end()
                                    ->scalarNode('prefix')->defaultNull()->end()
                                    ->enumNode('replication')->values(array(true, false, 'sentinel'))->end()
                                    ->scalarNode('service')->defaultNull()->end()
                                    ->enumNode('slave_failover')->values(array('none', 'error', 'distribute', 'distribute_slaves'))->end()
                                    ->arrayNode('parameters')
                                        ->canBeUnset()
                                        ->children()
                                            ->scalarNode('database')->defaultNull()->end()
                                            ->scalarNode('password')->defaultNull()->end()
                                            ->booleanNode('logging')->defaultValue($this->debug)->end()
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
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addMonologSection(ArrayNodeDefinition $rootNode)
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
