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

use Doctrine\Common\Cache\PredisCache;
use Doctrine\Common\Cache\RedisCache;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Config\Definition\BaseNode;
use function method_exists;
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

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('snc_redis');
        }

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
                        ->scalarNode('phpredis_clusterclient')->defaultValue('RedisCluster')->end()
                        ->scalarNode('phpredis_clusterclient_connection_wrapper')->defaultValue('Snc\RedisBundle\Client\Phpredis\ClientCluster')->end()
                        ->scalarNode('logger')->defaultValue('Snc\RedisBundle\Logger\RedisLogger')->end()
                        ->scalarNode('data_collector')->defaultValue('Snc\RedisBundle\DataCollector\RedisDataCollector')->end()
                        // class_exists are here for BC with doctrine/cache < 2
                        ->scalarNode('doctrine_cache_phpredis')->defaultValue(class_exists(RedisCache::class) ? RedisCache::class : RedisAdapter::class)->end()
                        ->scalarNode('doctrine_cache_predis')->defaultValue(class_exists(PredisCache::class) ? PredisCache::class : RedisAdapter::class)->end()
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
     * Adds the snc_redis.session configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addSessionSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('session')
                    ->setDeprecated('snc/redis-bundle', '3.6', 'Use Symfony built-int RedisSessionHandler instead.')
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
        $doctrineNode = $rootNode->children()
            ->arrayNode('doctrine')
            ->setDeprecated('snc/redis-bundle', '3.6', 'Set up your cache pools via framework.yaml and follow doctrine-bundle documentation to configure Doctrine to use them.')
            ->canBeUnset()
        ;
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
                                ->beforeNormalization()->ifString()->then(function ($v) {
                                    return (array) $v;
                                })->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                        ->fixXmlConfig('document_manager')
                        ->children()
                            ->arrayNode('document_managers')
                                ->defaultValue(array())
                                ->beforeNormalization()->ifString()->then(function ($v) {
                                    return (array) $v;
                                })->end()
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
                    ->setDeprecated('snc/redis-bundle', '3.6')
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
                    ->setDeprecated(...$this->getProfilerStorageDeprecationMessage())
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('client')->isRequired()->end()
                        ->scalarNode('ttl')->isRequired()->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Keep compatibility with symfony/config < 5.1
     *
     * The signature of method NodeDefinition::setDeprecated() has been updated to
     * NodeDefinition::setDeprecation(string $package, string $version, string $message).
     *
     * @return array
     */
    private function getProfilerStorageDeprecationMessage(): array
    {
        $message = 'Redis profiler storage is not available anymore since Symfony 4.4';

        if (method_exists(BaseNode::class, 'getDeprecation')) {
            return ['snc/redis-bundle', '3.2.0', $message];
        }

        return [$message];
    }
}
