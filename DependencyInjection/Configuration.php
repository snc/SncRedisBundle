<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * RedisBundle configuration class.
 *
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('snc_redis');

        $rootNode
            ->children()
                ->arrayNode('class')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('client')->defaultValue('Predis\Client')->end()
                        ->scalarNode('client_options')->defaultValue('Predis\ClientOptions')->end()
                        ->scalarNode('connection')->defaultValue('Snc\RedisBundle\Client\Predis\Network\LoggingStreamConnection')->end()
                        ->scalarNode('connection_parameters')->defaultValue('Predis\ConnectionParameters')->end()
                        ->scalarNode('connection_factory')->defaultValue('Snc\RedisBundle\Client\Predis\ConnectionFactory')->end()
                        ->scalarNode('logger')->defaultValue('Snc\RedisBundle\Logger\RedisLogger')->end()
                        ->scalarNode('data_collector')->defaultValue('Snc\RedisBundle\DataCollector\RedisDataCollector')->end()
                        ->scalarNode('doctrine_cache')->defaultValue('Snc\RedisBundle\Doctrine\Cache\RedisCache')->end()
                    ->end()
                ->end()
            ->end();

        $this->addConnectionsSection($rootNode);
        $this->addClientsSection($rootNode);
        $this->addSessionSection($rootNode);
        $this->addDoctrineSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds the snc_redis.connections configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addConnectionsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('connection')
            ->children()
                ->arrayNode('connections')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('scheme')->defaultValue('tcp')->end()
                            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                            ->scalarNode('port')->defaultValue(6379)->end()
                            ->scalarNode('path')->defaultNull()->end()
                            ->scalarNode('database')->defaultValue(0)->end()
                            ->scalarNode('password')->defaultNull()->end()
                            ->booleanNode('connection_async')->defaultFalse()->end()
                            ->booleanNode('connection_persistent')->defaultFalse()->end()
                            ->scalarNode('connection_timeout')->defaultValue(5)->end()
                            ->scalarNode('read_write_timeout')->defaultNull()->end()
                            ->scalarNode('alias')->isRequired()->end()
                            ->scalarNode('weight')->defaultNull()->end()
                            ->booleanNode('iterable_multibulk')->defaultFalse()->end()
                            ->booleanNode('throw_errors')->defaultTrue()->end()
                            ->booleanNode('logging')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
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
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->fixXmlConfig('connection')
                        ->children()
                            ->arrayNode('connections')
                                ->isRequired()
                                ->beforeNormalization()->ifString()->then(function($v) { return (array) $v; })->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('alias')->isRequired()->end()
                            ->arrayNode('options')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('profile')->defaultValue('2.0')
                                        ->beforeNormalization()
                                            ->ifTrue(function($v) { return false === is_string($v); })
                                            ->then(function($v) { return sprintf('%.1f', $v); })
                                        ->end()
                                    ->end()
                                    ->scalarNode('cluster')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds the snc_redis.session configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addSessionSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('session')
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('client')->isRequired()->end()
                        ->scalarNode('prefix')->defaultValue('session')->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds the snc_redis.doctrine configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addDoctrineSection(ArrayNodeDefinition $rootNode)
    {
        $doctrineNode = $rootNode->children()->arrayNode('doctrine')->canBeUnset();
        foreach (array('metadata_cache', 'result_cache', 'query_cache') as $type) {
            $doctrineNode
                ->children()
                    ->arrayNode($type)
                        ->canBeUnset()
                        ->children()
                            ->scalarNode('client')->isRequired()->end()
                            ->scalarNode('namespace')->defaultNull()->end()
                        ->end()
                        ->fixXmlConfig('entity_manager')
                        ->children()
                            ->arrayNode('entity_managers')
                                ->defaultValue(array())
                                ->beforeNormalization()->ifString()->then(function($v) { return (array) $v; })->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                        ->fixXmlConfig('document_manager')
                        ->children()
                            ->arrayNode('document_managers')
                                ->defaultValue(array())
                                ->beforeNormalization()->ifString()->then(function($v) { return (array) $v; })->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();
        }
    }
}
