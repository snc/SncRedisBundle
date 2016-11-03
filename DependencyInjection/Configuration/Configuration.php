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
 * RedisBundle configuration class.
 *
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
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('snc_redis');

        $rootNode
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
                        ->scalarNode('phpredis_connection_wrapper')->defaultValue('Snc\RedisBundle\Client\Phpredis\Client')->end()
                        ->scalarNode('logger')->defaultValue('Snc\RedisBundle\Logger\RedisLogger')->end()
                        ->scalarNode('data_collector')->defaultValue('Snc\RedisBundle\DataCollector\RedisDataCollector')->end()
                        ->scalarNode('doctrine_cache_phpredis')->defaultValue('Doctrine\Common\Cache\RedisCache')->end()
                        ->scalarNode('doctrine_cache_predis')->defaultValue('Doctrine\Common\Cache\PredisCache')->end()
                        ->scalarNode('monolog_handler')->defaultValue('Monolog\Handler\RedisHandler')->end()
                        ->scalarNode('swiftmailer_spool')->defaultValue('Snc\RedisBundle\SwiftMailer\RedisSpool')->end()
                    ->end()
                ->end()
            ->end();

        $this->addClientsSection($rootNode);
        $this->addSessionSection($rootNode);
        $this->addDoctrineSection($rootNode);
        $this->addMonologSection($rootNode);
        $this->addSwiftMailerSection($rootNode);
        $this->addProfilerStorageSection($rootNode);

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
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->fixXmlConfig('dsn')
                        ->children()
                            ->scalarNode('type')->isRequired()
                                ->validate()
                                    ->ifTrue(function($v) { return !in_array($v, array('predis', 'phpredis')); })
                                    ->thenInvalid('The redis client type %s is invalid.')
                                ->end()
                            ->end()
                            ->scalarNode('alias')->isRequired()->end()
                            ->booleanNode('logging')->defaultValue($this->debug)->end()
                            ->arrayNode('dsns')
                                ->isRequired()
                                ->performNoDeepMerging()
                                ->beforeNormalization()
                                    ->ifString()->then(function($v) { return (array) $v; })
                                ->end()
                                ->beforeNormalization()
                                    ->always()->then(function($v) {
                                        return array_map(function($dsn) {
                                            $parsed = new RedisDsn($dsn);

                                            return $parsed->isValid() ? $parsed : $dsn;
                                        }, $v);
                                    })
                                ->end()
                                ->prototype('variable')
                                    ->validate()
                                        ->ifTrue(function($v) { return is_string($v); })
                                        ->thenInvalid('The redis DSN %s is invalid.')
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('alias')->isRequired()->end()
                            ->arrayNode('options')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('connection_async')->defaultFalse()->end()
                                    ->booleanNode('connection_persistent')->defaultFalse()->end()
                                    ->scalarNode('connection_timeout')->defaultValue(5)->end()
                                    ->scalarNode('read_write_timeout')->defaultNull()->end()
                                    ->booleanNode('iterable_multibulk')->defaultFalse()->end()
                                    ->booleanNode('throw_errors')->defaultTrue()->end()
                                    ->scalarNode('profile')->defaultValue('default')
                                        ->beforeNormalization()
                                            ->ifTrue(function($v) { return false === is_string($v); })
                                            ->then(function($v) { return sprintf('%.1F', $v); })
                                        ->end()
                                    ->end()
                                    ->scalarNode('cluster')->defaultNull()->end()
                                    ->scalarNode('prefix')->defaultNull()->end()
                                    ->booleanNode('replication')->defaultFalse()->end()
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
                        ->scalarNode('ttl')->end()
                        ->booleanNode('locking')->defaultTrue()->end()
                        ->scalarNode('spin_lock_wait')->defaultValue(150000)->end()
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
        foreach (array('metadata_cache', 'result_cache', 'query_cache', 'second_level_cache') as $type) {
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

    /**
     * Adds the snc_redis.swiftmailer configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addSwiftMailerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('swiftmailer')
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('client')->isRequired()->end()
                        ->scalarNode('key')->isRequired()->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds the snc_redis.profiler_storage configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addProfilerStorageSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('profiler_storage')
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('client')->isRequired()->end()
                        ->scalarNode('ttl')->isRequired()->end()
                    ->end()
                ->end()
            ->end();
    }
}
