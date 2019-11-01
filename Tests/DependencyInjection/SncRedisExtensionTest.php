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

use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\DependencyInjection\Configuration\Configuration;
use Snc\RedisBundle\DependencyInjection\SncRedisExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Parser;

/**
 * SncRedisExtensionTest
 */
class SncRedisExtensionTest extends TestCase
{
    /**
     * @static
     *
     * @return array
     */
    public static function parameterValues()
    {
        return array(
            array('snc_redis.client.class', 'Predis\Client'),
            array('snc_redis.client_options.class', 'Predis\Configuration\Options'),
            array('snc_redis.connection_parameters.class', 'Predis\Connection\Parameters'),
            array('snc_redis.connection_factory.class', 'Snc\RedisBundle\Client\Predis\Connection\ConnectionFactory'),
            array('snc_redis.connection_wrapper.class', 'Snc\RedisBundle\Client\Predis\Connection\ConnectionWrapper'),
            array('snc_redis.logger.class', 'Snc\RedisBundle\Logger\RedisLogger'),
            array('snc_redis.data_collector.class', 'Snc\RedisBundle\DataCollector\RedisDataCollector'),
            array('snc_redis.doctrine_cache_phpredis.class','Doctrine\Common\Cache\RedisCache'),
            array('snc_redis.doctrine_cache_predis.class','Doctrine\Common\Cache\PredisCache'),
            array('snc_redis.monolog_handler.class', 'Monolog\Handler\RedisHandler'),
            array('snc_redis.swiftmailer_spool.class', 'Snc\RedisBundle\SwiftMailer\RedisSpool'),
        );
    }

    public function testEmptyConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = array();
        $extension->load(array($config), $container = $this->getContainer());
        $this->assertArrayNotHasKey('snc_redis.client', $container->getDefinitions());
    }

    /**
     * @param string $name     Name
     * @param string $expected Expected value
     *
     * @dataProvider parameterValues
     */
    public function testDefaultParameterConfigLoad($name, $expected)
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertEquals($expected, $container->getParameter($name));
    }

    /**
     * @param string $name     Name
     * @param string $expected Expected value
     *
     * @dataProvider parameterValues
     */
    public function testNoClientsConfigLoad($name, $expected)
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getNoClientYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertEquals($expected, $container->getParameter($name));
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testDefaultClientTaggedServicesConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertCount(1, $container->findTaggedServiceIds('snc_redis.client'), 'Minimal Yaml should have tagged 1 client');
    }

    /**
     * Test loading of minimal config
     */
    public function testMinimalConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());
        $this->assertTrue($container->hasDefinition('snc_redis.logger'));
        $this->assertTrue($container->hasDefinition('snc_redis.data_collector'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.default_parameters.default'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.default'));
        $this->assertFalse($container->hasAlias('snc_redis.default_client'));
        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(array('snc_redis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * @group legacy
     *
     * Test loading of full config
     */
    public function testFullConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getFullYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertTrue($container->hasDefinition('snc_redis.logger'));
        $this->assertTrue($container->hasDefinition('snc_redis.data_collector'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.default_parameters.default'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.default'));
        $this->assertFalse($container->hasAlias('snc_redis.default_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.cache_parameters.cache'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.cache_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.cache_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.cache'));
        $this->assertFalse($container->hasAlias('snc_redis.cache_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.monolog_parameters.monolog'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.monolog_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.monolog_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.monolog'));
        $this->assertFalse($container->hasAlias('snc_redis.monolog_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.cluster1_parameters.cluster'));
        $this->assertTrue($container->hasDefinition('snc_redis.connection.cluster2_parameters.cluster'));
        $this->assertTrue($container->hasDefinition('snc_redis.connection.cluster3_parameters.cluster'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.cluster_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.cluster_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.cluster'));
        $this->assertFalse($container->hasAlias('snc_redis.cluster_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.session.handler'));

        $this->assertTrue($container->hasDefinition('doctrine.orm.default_metadata_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.default_result_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.default_query_cache'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.default_second_level_cache.region_cache_driver'));
        $this->assertTrue($container->hasDefinition('doctrine.orm.read_result_cache'));

        $this->assertTrue($container->hasDefinition('doctrine_mongodb.odm.default_metadata_cache'));
        $this->assertTrue($container->hasDefinition('doctrine_mongodb.odm.default_result_cache'));
        $this->assertTrue($container->hasDefinition('doctrine_mongodb.odm.default_query_cache'));
        $this->assertTrue($container->hasDefinition('doctrine_mongodb.odm.slave1_result_cache'));
        $this->assertTrue($container->hasDefinition('doctrine_mongodb.odm.slave2_result_cache'));

        $this->assertTrue($container->hasDefinition('snc_redis.monolog'));
        $this->assertFalse($container->hasAlias('snc_redis.monolog_client'));
        $this->assertTrue($container->hasDefinition('snc_redis.monolog.handler'));

        $this->assertTrue($container->hasDefinition('snc_redis.swiftmailer.spool'));
        $this->assertTrue($container->hasAlias('swiftmailer.spool.redis'));

        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertGreaterThanOrEqual(4, count($container->findTaggedServiceIds('snc_redis.client')), 'expected at least 4 tagged clients');

        $tags = $container->findTaggedServiceIds('snc_redis.client');
        $this->assertArrayHasKey('snc_redis.default', $tags);
        $this->assertArrayHasKey('snc_redis.cache', $tags);
        $this->assertArrayHasKey('snc_redis.monolog', $tags);
        $this->assertArrayHasKey('snc_redis.cluster', $tags);
        $this->assertArraySubset(array('snc_redis.cache' => array(array('alias' => 'cache'))), $tags);
        $this->assertArraySubset(array('snc_redis.cluster' => array(array('alias' => 'cluster'))), $tags);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage requires it to reference either an entity manager or document manager
     */
    public function testInvalidDoctrineCacheConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getInvalidDoctrineCacheConfig());
        $extension->load(array($config), $this->getContainer());
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage You have to disable logging for the client
     */
    public function testInvalidMonologConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getInvalidMonologYamlConfig());
        $extension->load(array($config), $this->getContainer());
    }

    /**
     * Test the monolog formatter option
     */
    public function testMonologFormatterOption()
    {
        $container = $this->getContainer();
        //Create a fake formatter definition
        $container->setDefinition('my_monolog_formatter', new Definition('Monolog\\Formatter\\LogstashFormatter', array('symfony')));
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getMonologFormatterOptionYamlConfig());
        $extension->load(array($config), $container);

        $loggerDefinition = $container->getDefinition('snc_redis.monolog.handler');
        $calls = $loggerDefinition->getMethodCalls();
        $this->assertTrue($loggerDefinition->hasMethodCall('setFormatter'));
        $calls = $loggerDefinition->getMethodCalls();
        foreach ($calls as $call) {
            if ($call[0] === 'setFormatter') {
                $this->assertEquals('my_monolog_formatter', (string) $call[1][0]);
                break;
            }
        }
    }

    /**
     * @group legacy
     *
     * Test valid parsing of the client profile option
     */
    public function testClientProfileOption()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getFullYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $profileDefinition = $container->getDefinition('snc_redis.client.default_profile');
        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);

        $this->assertSame((float) 2, $config['clients']['default']['options']['profile'], 'Profile version 2.0 was parsed as float');
        $this->assertSame('Predis\\Profile\\RedisVersion200', $profileDefinition->getClass(), 'Profile definition is instance of Predis\\Profile\\RedisVersion200');

        $this->assertSame('snc:', $options['prefix'], 'Prefix option was allowed');
    }

    /**
     * Test multiple clients both containing "master" dsn aliases
     */
    public function testMultipleClientMaster()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getMultipleReplicationYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $defaultParameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.master_parameters.default', (string) $defaultParameters[0]);
        $defaultMasterParameters = $container->getDefinition((string) $defaultParameters[0])->getArgument(0);
        $this->assertEquals('defaultprefix', $defaultMasterParameters['prefix']);

        $secondParameters = $container->getDefinition('snc_redis.second')->getArgument(0);
        $this->assertEquals('snc_redis.connection.master_parameters.second', (string) $secondParameters[0]);
        $secondMasterParameters = $container->getDefinition((string) $secondParameters[0])->getArgument(0);
        $this->assertEquals('secondprefix', $secondMasterParameters['prefix']);
    }

    /**
     * Test valid XML config
     *
     * @doesNotPerformAssertions
     */
    public function testValidXmlConfig()
    {
        $container = $this->getContainer();
        $container->registerExtension(new SncRedisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config/xml'));
        $loader->load('valid.xml');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidXmlConfig()
    {
        $container = $this->getContainer();
        $container->registerExtension(new SncRedisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config/xml'));
        $loader->load('invalid.xml');
    }

    /**
     * Test config merging
     */
    public function testConfigurationMerging()
    {
        $configuration = new Configuration(true);
        $configs = array($this->parseYaml($this->getMergeConfig1()), $this->parseYaml($this->getMergeConfig2()));
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);
        $this->assertCount(1, $config['clients']['default']['dsns']);
        $this->assertEquals('redis://test', current($config['clients']['default']['dsns']));
    }

    /**
     * Test valid config of the replication option
     */
    public function testClientReplicationOption()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getReplicationYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertTrue($options['replication']);
        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.master_parameters.default', (string) $parameters[0]);
        $masterParameters = $container->getDefinition((string) $parameters[0])->getArgument(0);
        $this->assertTrue($masterParameters['replication']);

        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(array('snc_redis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * Test valid config of the serialization option
     */
    public function testClientSerializationOption()
    {
         $extension = new SncRedisExtension();
         $config = $this->parseYaml($this->getSerializationYamlConfig());
         $extension->load(array($config), $container = $this->getContainer());
         $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
         $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
         $masterParameters = $container->getDefinition((string) $parameters[0])->getArgument(0);
         $this->assertSame($options['serialization'], $masterParameters['serialization']);
    }

    /**
     * Test valid config of the single host sentinel replication option
     */
    public function testSingleSentinelOption()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getSingleSentinelYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertEquals('sentinel', $options['replication']);
        $this->assertEquals('mymaster', $options['service']);
        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.default_parameters.default', (string) $parameters[0]);
        $masterParameters = $container->getDefinition((string) $parameters[0])->getArgument(0);
        $this->assertEquals('sentinel', $masterParameters['replication']);
        $this->assertEquals('mymaster', $masterParameters['service']);
        $this->assertInternalType('array', $masterParameters['parameters']);
        $this->assertEquals('1', $masterParameters['parameters']['database']);
        $this->assertEquals('pass', $masterParameters['parameters']['password']);

        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(array('snc_redis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * Test valid config of the sentinel replication option
     */
    public function testSentinelOption()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getSentinelYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertEquals('sentinel', $options['replication']);
        $this->assertEquals('mymaster', $options['service']);
        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.master_parameters.default', (string) $parameters[0]);
        $masterParameters = $container->getDefinition((string) $parameters[0])->getArgument(0);
        $this->assertEquals('sentinel', $masterParameters['replication']);
        $this->assertEquals('mymaster', $masterParameters['service']);
        $this->assertInternalType('array', $masterParameters['parameters']);
        $this->assertEquals('1', $masterParameters['parameters']['database']);
        $this->assertEquals('pass', $masterParameters['parameters']['password']);

        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(array('snc_redis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * Test valid config of the cluster option
     */
    public function testClusterOption()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getClusterYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertEquals('redis', $options['cluster']);
        $this->assertFalse(array_key_exists('replication', $options));

        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.default1_parameters.default', (string) $parameters[0]);
        $this->assertEquals('snc_redis.connection.default2_parameters.default', (string) $parameters[1]);

        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(array('snc_redis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * Test provided options are respected
     */
    public function testPhpRedisParameters()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getPhpRedisYamlConfigWithParameters());
        $extension->load(array($config), $container = $this->getContainer());

        $defaultParameters = $container->getDefinition('snc_redis.default');

        $this->assertSame(1, $defaultParameters->getArgument(2)['parameters']['database']);
        $this->assertSame('pass', $defaultParameters->getArgument(2)['parameters']['password']);

        $redis = $container->get('snc_redis.default');

        $this->assertSame(1, $redis->getDBNum());
        $this->assertSame('pass', $redis->getAuth());
    }

    /**
     * Test parameters provided at DSN overrides the provided options
     */
    public function testPhpRedisDuplicatedParameters()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getPhpRedisYamlConfigWithDuplicatedParameters());
        $extension->load(array($config), $container = $this->getContainer());

        $defaultParameters = $container->getDefinition('snc_redis.default');

        $this->assertSame(2, $defaultParameters->getArgument(2)['parameters']['database']);
        $this->assertSame('word', $defaultParameters->getArgument(2)['parameters']['password']);

        $redis = $container->get('snc_redis.default');

        $this->assertSame(1, $redis->getDBNum());
        $this->assertSame('pass', $redis->getAuth());
    }

    /**
     * Test minimal RedisCluster configuration
     */
    public function testPhpRedisClusterParameters()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getPhpRedisClusterYamlMinimalConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $defaultParameters = $container->getDefinition('snc_redis.default');

        $redis = $container->get('snc_redis.default');

        $this->assertInstanceOf('\RedisCluster', $redis);
    }

    private function parseYaml($yaml)
    {
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function getSerializationYamlConfig()
    {
         return <<<'EOF'
clients:
 default:
     type: predis
     alias: default
     dsn:
         - redis://localhost?alias=master
         - redis://otherhost
     options:
         serialization: "default"
EOF;
    }

    private function getNoClientYamlConfig()
    {
        return <<<'EOF'
clients:
EOF;
    }

    private function getMinimalYamlConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
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
            prefix: snc:
    cache:
        type: predis
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
        dsn:
            - redis://127.0.0.1/1
            - redis://127.0.0.2/2
            - redis://pw@/var/run/redis/redis-1.sock/10
            - redis://pw@127.0.0.1:63790/10
        options:
            profile: 2.4
            connection_timeout: 10
            connection_persistent: true
            read_write_timeout: 30
            iterable_multibulk: false
            throw_errors: true
            cluster: predis
            parameters:
                database: 1
                password: pass
session:
    client: default
    prefix: foo
    ttl: 1440
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
    second_level_cache:
        client: cache
        entity_manager: default
        document_manager: default
monolog:
    client: monolog
    key: monolog
swiftmailer:
    client: default
    key: swiftmailer
profiler_storage:
    client: default
    ttl: 3600
EOF;
    }

    private function getInvalidDoctrineCacheConfig()
    {
        return <<<'EOF'
clients:
    cache:
        type: predis
        dsn: redis://localhost
doctrine:
    metadata_cache:
        client: cache
    result_cache:
        client: cache
    query_cache:
        client: cache
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

    private function getMonologFormatterOptionYamlConfig()
    {
        return <<<'EOF'
clients:
    monolog:
        type: predis
        alias: monolog
        dsn: redis://localhost
        logging: false
monolog:
    client: monolog
    key: monolog
    formatter: my_monolog_formatter
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

    private function getReplicationYamlConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        alias: default
        dsn:
            - redis://localhost?alias=master
            - redis://otherhost
        options:
            replication: true
EOF;
    }

    private function getSingleSentinelYamlConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        alias: default
        dsn: redis://localhost
        options:
            replication: sentinel
            service: mymaster
            parameters:
                database: 1
                password: pass
EOF;
    }

    private function getSentinelYamlConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        alias: default
        dsn:
            - redis://localhost?alias=master
            - redis://otherhost
        options:
            replication: sentinel
            service: mymaster
            parameters:
                database: 1
                password: pass
EOF;
    }

    private function getClusterYamlConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        alias: default
        dsn:
            - redis://127.0.0.1/1
            - redis://127.0.0.2/2
        options:
            cluster: "redis"
EOF;
    }

    private function getMultipleReplicationYamlConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        alias: default
        dsn:
            - redis://defaulthost?alias=master
            - redis://defaultslave
        options:
            replication: true
            prefix: defaultprefix
    second:
        type: predis
        alias: second
        dsn:
            - redis://secondmaster?alias=master
            - redis://secondslave
        options:
            replication: true
            prefix: secondprefix
EOF;
    }

    private function getPhpRedisYamlConfigWithParameters()
    {
        return <<<'EOF'
clients:
    default:
        type: phpredis
        alias: default
        dsn: redis://localhost
        options:
            parameters:
                database: 1
                password: pass
EOF;
    }

    private function getPhpRedisYamlConfigWithDuplicatedParameters()
    {
        return <<<'EOF'
clients:
    default:
        type: phpredis
        alias: default
        dsn: redis://redis:pass@localhost/1
        options:
            parameters:
                database: 2
                password: word
EOF;
    }

    private function getPhpRedisClusterYamlMinimalConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: phpredis
        alias: default
        dsn: ["redis://localhost:7000/0"]
        options:
            cluster: true
EOF;
    }
    
    private function getContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
            'kernel.bundles'     => array(),
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir'    => __DIR__.'/../../' // src dir
        )));
    }
}
