<?php

namespace Snc\RedisBundle\Tests\DependencyInjection;

use Snc\RedisBundle\DependencyInjection\SncRedisExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Yaml\Parser;

class SncRedisExtensionTest extends \PHPUnit_Framework_TestCase
{
    public static function parameterValues()
    {
        return array(
            array('redis.client.class', 'Predis\Client'),
            array('redis.client_options.class', 'Predis\ClientOptions'),
            array('redis.connection.class', 'Snc\RedisBundle\Client\Predis\Network\LoggingStreamConnection'),
            array('redis.connection_parameters.class', 'Predis\ConnectionParameters'),
            array('redis.connection_factory.class', 'Snc\RedisBundle\Client\Predis\ConnectionFactory'),
            array('redis.logger.class', 'Snc\RedisBundle\Logger\RedisLogger'),
            array('redis.data_collector.class', 'Snc\RedisBundle\DataCollector\RedisDataCollector'),
            array('redis.doctrine_cache.class', 'Snc\RedisBundle\Doctrine\Cache\RedisCache'),
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

        $this->assertTrue($container->hasDefinition('redis.logger'));
        $this->assertTrue($container->hasDefinition('redis.data_collector'));
        $this->assertTrue($container->hasDefinition('redis.connectionfactory'));

        $this->assertTrue($container->hasDefinition('redis.connection.default_parameters'));
        $this->assertTrue($container->hasDefinition('redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('redis.default_client'));
    }

    public function testFullConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getFullYamlConfig());
        $extension->load(array($config), $container = new ContainerBuilder());

        $this->assertTrue($container->hasDefinition('redis.logger'));
        $this->assertTrue($container->hasDefinition('redis.data_collector'));
        $this->assertTrue($container->hasDefinition('redis.connectionfactory'));

        $this->assertTrue($container->hasDefinition('redis.connection.default_parameters'));
        $this->assertTrue($container->hasDefinition('redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('redis.default_client'));

        $this->assertTrue($container->hasDefinition('redis.connection.cache_parameters'));
        $this->assertTrue($container->hasDefinition('redis.client.cache_options'));
        $this->assertTrue($container->hasDefinition('redis.cache_client'));

        $this->assertTrue($container->hasDefinition('redis.connection.session_parameters'));
        $this->assertTrue($container->hasDefinition('redis.client.session_options'));
        $this->assertTrue($container->hasDefinition('redis.session_client'));

        $this->assertTrue($container->hasDefinition('redis.connection.cluster1_parameters'));
        $this->assertTrue($container->hasDefinition('redis.connection.cluster2_parameters'));
        $this->assertTrue($container->hasDefinition('redis.connection.cluster3_parameters'));
        $this->assertTrue($container->hasDefinition('redis.client.cluster_options'));
        $this->assertTrue($container->hasDefinition('redis.cluster_client'));

        $this->assertTrue($container->hasDefinition('redis.session.storage'));

        $this->assertTrue($container->hasDefinition('doctrine.orm.default_metadata_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.default_result_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.default_query_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.read_result_cache'));

        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.default_metadata_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.default_result_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.default_query_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.slave1_result_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.odm.mongodb.slave2_result_cache'));
    }

    public function testClientProfileOption()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getFullYamlConfig());
        $extension->load(array($config), $container = new ContainerBuilder());

        $options = $container->getDefinition('redis.client.default_options')->getArgument(0);

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

    private function parseYaml($yaml)
    {
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function getMinimalYamlConfig()
    {
        return <<<'EOF'
connections:
    default:
        alias: default
clients:
    default:
        alias: default
        connection: default
EOF;
    }

    private function getFullYamlConfig()
    {
        return <<<'EOF'
connections:
    default:
        alias: default
        host: localhost
        port: 6379
        database: 0
        logging: true
    cache:
        alias: cache
        host: localhost
        port: 6379
        database: 1
        password: secret
        connection_timeout: 10
        read_write_timeout: 30
        iterable_multibulk: false
        throw_errors: true
        logging: false
    session:
        alias: session
        host: localhost
        port: 6379
        database: 2
    cluster1:
        alias: cluster1
        host: localhost
        port: 6379
        database: 3
        weight: 10
    cluster2:
        alias: cluster2
        host: localhost
        port: 6379
        database: 4
        weight: 5
    cluster3:
        alias: cluster3
        host: localhost
        port: 6379
        database: 5
        weight: 1
clients:
    default:
        alias: default
        connection: default
        options:
            profile: 2.0
    cache:
        alias: cache
        connection: cache
        options:
            profile: 2.2
    session:
        alias: session
        connection: session
        options:
            profile: 1.2
    cluster:
        alias: cluster
        connection: [ cluster1, cluster2, cluster3 ]
        options:
            profile: DEV
            cluster: Snc\RedisBundle\Client\Predis\Network\PredisCluster
session:
    client: session
    prefix: foo
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
EOF;
    }
}
