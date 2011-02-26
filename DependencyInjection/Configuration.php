<?php

namespace Snc\RedisBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * RedisBundle configuration class.
 *
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 */
class Configuration
{
    /**
     * Returns the redis configuration tree.
     *
     * @return \Symfony\Component\DependencyInjection\Configuration\NodeInterface
     */
    public function getConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('redis', 'array');

        $rootNode
            ->arrayNode('class')
                ->addDefaultsIfNotSet()
                    ->scalarNode('client')->defaultValue('Predis\Client')->end()
                    ->scalarNode('connection')->defaultValue('Snc\RedisBundle\Client\Predis\LoggingConnection')->end()
                    ->scalarNode('connection_parameters')->defaultValue('Predis\ConnectionParameters')->end()
                    ->scalarNode('logger')->defaultValue('Snc\RedisBundle\Logger\RedisLogger')->end()
                    ->scalarNode('data_collector')->defaultValue('Snc\RedisBundle\DataCollector\RedisDataCollector')->end()
                    ->scalarNode('doctrine_cache')->defaultValue('Snc\RedisBundle\Doctrine\Cache\RedisCache')->end()
            ->end();

        $this->addConnectionsSection($rootNode);
        $this->addClientsSection($rootNode);
        $this->addSessionSection($rootNode);
        $this->addDoctrineSection($rootNode);

        return $treeBuilder->buildTree();
    }

    /**
     * Adds the redis.connections configuration
     *
     * @param NodeBuilder $rootNode
     */
    private function addConnectionsSection(NodeBuilder $rootNode)
    {
        $rootNode
            ->fixXmlConfig('connection')
            ->arrayNode('connections')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('alias', false)
                ->prototype('array')
                    ->scalarNode('scheme')->defaultValue('tcp')->end()
                    ->scalarNode('host')->defaultValue('localhost')->end()
                    ->scalarNode('port')->defaultValue(6379)->end()
                    ->scalarNode('path')->defaultNull()->end()
                    ->scalarNode('database')->defaultValue(0)->end()
                    ->scalarNode('password')->defaultNull()->end()
                    ->booleanNode('connection_async')->defaultFalse()->end()
                    ->booleanNode('connection_persistent')->defaultFalse()->end()
                    ->scalarNode('connection_timeout')->defaultValue(5)->end()
                    ->scalarNode('read_write_timeout')->defaultNull()->end()
                    ->scalarNode('weight')->defaultNull()->end()
                    ->booleanNode('logging')->defaultFalse()->end()
                    ->scalarNode('alias')->isRequired()->end()
                ->end()
            ->end();
    }

    /**
     * Adds the redis.clients configuration
     *
     * @param NodeBuilder $rootNode
     */
    private function addClientsSection(NodeBuilder $rootNode)
    {
        $rootNode
            ->fixXmlConfig('client')
            ->arrayNode('clients')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('alias', false)
                ->prototype('array')
                    ->fixXmlConfig('connection')
                    ->arrayNode('connections')
                        ->isRequired()
                        ->beforeNormalization()->ifString()->then(function($v) { return (array) $v; })->end()
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('alias')->isRequired()->end()
                ->end()
            ->end();
    }

    /**
     * Adds the redis.session configuration
     *
     * @param NodeBuilder $rootNode
     */
    private function addSessionSection(NodeBuilder $rootNode)
    {
        $rootNode
            ->arrayNode('session')
                ->canBeUnset()
                ->scalarNode('client')->isRequired()->end()
                ->scalarNode('prefix')->defaultValue('session')->end()
            ->end();
    }

    /**
     * Adds the redis.doctrine configuration
     *
     * @param NodeBuilder $rootNode
     */
    private function addDoctrineSection(NodeBuilder $rootNode)
    {
        $doctrineNode = $rootNode->arrayNode('doctrine')->canBeUnset();
        foreach (array('metadata_cache', 'result_cache', 'query_cache') as $type) {
            $doctrineNode
                ->arrayNode($type)
                    ->canBeUnset()
                    ->scalarNode('client')->isRequired()->end()
                    ->scalarNode('namespace')->defaultNull()->end()
                    ->fixXmlConfig('entity_manager')
                    ->arrayNode('entity_managers')
                        ->defaultValue(array('default'))
                        ->beforeNormalization()->ifString()->then(function($v) { return (array) $v; })->end()
                        ->prototype('scalar')->end()
                    ->end()
                ->end();
        }
    }
}
