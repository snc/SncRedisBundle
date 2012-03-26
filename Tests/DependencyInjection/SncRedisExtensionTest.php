<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Tests\DependencyInjection;

use Snc\RedisBundle\DependencyInjection\Configuration\Configuration;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Snc\RedisBundle\DependencyInjection\SncRedisExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Yaml\Parser;

class SncRedisExtensionTest extends \PHPUnit_Framework_TestCase
{
    public static function parameterValues()
    {
        return array(
            array('snc_redis.client.class', 'Predis\Client'),
            array('snc_redis.client_options.class', 'Predis\Options\ClientOptions'),
            array('snc_redis.connection_parameters.class', 'Predis\ConnectionParameters'),
            array('snc_redis.connection_factory.class', 'Snc\RedisBundle\Client\Predis\ConnectionFactory'),
            array('snc_redis.connection_wrapper.class', 'Snc\RedisBundle\Client\Predis\Network\ConnectionWrapper'),
            array('snc_redis.logger.class', 'Snc\RedisBundle\Logger\RedisLogger'),
            array('snc_redis.data_collector.class', 'Snc\RedisBundle\DataCollector\RedisDataCollector'),
            array('snc_redis.doctrine_cache.class', 'Snc\RedisBundle\Doctrine\Cache\RedisCache'),
            array('snc_redis.monolog_handler.class', 'Snc\RedisBundle\Monolog\Handler\RedisHandler'),
            array('snc_redis.swiftmailer_spool.class', 'Snc\RedisBundle\SwiftMailer\RedisSpool'),
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testEmptyConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = array();
        $extension->load(array($config), $container = new ContainerBuilder());
    }

    /**
     * @dataProvider parameterValues
     */
    public function testDefaultParameterConfigLoad($name, $expected)
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load(array($config), $container = new ContainerBuilder());

        $this->assertEquals($expected, $container->getParameter($name));
    }

    public function testMinimalConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load(array($config), $container = new ContainerBuilder());

        $this->assertTrue($container->hasDefinition('snc_redis.logger'));
        $this->assertTrue($container->hasDefinition('snc_redis.data_collector'));
        $this->assertTrue($container->hasDefinition('snc_redis.connectionfactory'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.default_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.default'));
        $this->assertTrue($container->hasAlias('snc_redis.default_client'));
    }

    public function testFullConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getFullYamlConfig());
        $extension->load(array($config), $container = new ContainerBuilder());

        $this->assertTrue($container->hasDefinition('snc_redis.logger'));
        $this->assertTrue($container->hasDefinition('snc_redis.data_collector'));
        $this->assertTrue($container->hasDefinition('snc_redis.connectionfactory'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.default_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.default'));
        $this->assertTrue($container->hasAlias('snc_redis.default_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.cache_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.cache_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.cache'));
        $this->assertTrue($container->hasAlias('snc_redis.cache_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.cluster1_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.connection.cluster2_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.connection.cluster3_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.cluster_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.cluster'));
        $this->assertTrue($container->hasAlias('snc_redis.cluster_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.session.handler'));

        $this->assertTrue($container->hasDefinition('doctrine.orm.default_metadata_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.default_result_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.default_query_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.read_result_cache'));

        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.default_metadata_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.default_result_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.default_query_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.slave1_result_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.slave2_result_cache'));

        $this->assertTrue($container->hasDefinition('snc_redis.monolog'));
        $this->assertTrue($container->hasAlias('snc_redis.monolog_client'));
        $this->assertTrue($container->hasDefinition('monolog.handler.redis'));

        $this->assertTrue($container->hasDefinition('snc_redis.swiftmailer.spool'));
        $this->assertTrue($container->hasAlias('swiftmailer.spool'));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidMonologConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getInvalidMonologYamlConfig());
        $extension->load(array($config), $container = new ContainerBuilder());
    }

    public function testClientProfileOption()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getFullYamlConfig());
        $extension->load(array($config), $container = new ContainerBuilder());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);

        $this->assertSame((float)2, $config['clients']['default']['options']['profile'], 'Profile version 2.0 was parsed as float');
        $this->assertSame('2.0', $options['profile'], 'Profile option was converted to a string');
    }

    public function testValidXmlConfig()
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new SncRedisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config'));
        $loader->load('valid.xml');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidXmlConfig()
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new SncRedisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config'));
        try {
            $loader->load('invalid.xml');
        } catch (\Exception $e) {
            $this->assertContains("The attribute 'alias' is required but missing.", $e->getMessage());
            throw $e;
        }
    }

    public function testConfigurationMerging()
    {
        $configuration = new Configuration();
        $configs = array($this->parseYaml($this->getMergeConfig1()), $this->parseYaml($this->getMergeConfig2()));
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);
        $this->assertCount(1, $config['clients']['default']['dsns']);
        $this->assertEquals(new RedisDsn('redis://test'), current($config['clients']['default']['dsns']));
    }

    private function parseYaml($yaml)
    {
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function getMinimalYamlConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        alias: default
        dsn: redis://localhost
EOF;
    }

    private function getFullYamlConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        alias: default
        dsn: redis://localhost
        logging: true
        options:
            profile: 2.0
    cache:
        type: predis
        alias: cache
        dsn: redis://localhost/1
        logging: true
    monolog:
        type: predis
        alias: monolog
        dsn: redis://localhost/1
        logging: false
    cluster:
        type: predis
        alias: cluster
        dsn: [ redis://127.0.0.1/1, redis://127.0.0.2/2, redis://pw@/var/run/redis/redis-1.sock:63790/10, redis://pw@127.0.0.1:63790/10 ]
        options:
            profile: 2.4
            connection_timeout: 10
            connection_persistent: true
            read_write_timeout: 30
            iterable_multibulk: false
            throw_errors: true
            cluster: Snc\RedisBundle\Client\Predis\Network\PredisCluster
session:
    client: session
    prefix: foo
    use_as_default: false
doctrine:
    metadata_cache:
        client: cache
        entity_manager: default
        document_manager: default
    result_cache:
        client: cache
        entity_manager: [default, read]
        document_manager: [default, slave1, slave2]
        namespace: "dcrc:"
    query_cache:
        client: cache
        entity_manager: default
        document_manager: default
monolog:
    client: monolog
    key: monolog
swiftmailer:
    client: default
    key: swiftmailer
EOF;
    }

    private function getInvalidMonologYamlConfig()
    {
        return <<<'EOF'
clients:
    monolog:
        type: predis
        alias: monolog
        dsn: redis://localhost
        logging: true
monolog:
    client: monolog
    key: monolog
EOF;
    }

    private function getMergeConfig1()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        alias: default
        dsn: [ redis://default/1, redis://default/2 ]
        logging: true
EOF;
    }

    private function getMergeConfig2()
    {
        return <<<'EOF'
clients:
    default:
        dsn: redis://test
EOF;
    }
}
