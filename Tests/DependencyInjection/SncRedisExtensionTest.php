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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Parser;

/**
 * SncRedisExtensionTest
 */
class SncRedisExtensionTest extends \PHPUnit_Framework_TestCase
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
        $extension->load(array($config), $this->getContainer());
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
     * Test loading of minimal config
     */
    public function testMinimalConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertTrue($container->hasDefinition('snc_redis.logger'));
        $this->assertTrue($container->hasDefinition('snc_redis.data_collector'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.default_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.default'));
        $this->assertTrue($container->hasAlias('snc_redis.default_client'));
    }

    /**
     * Test loading of full config
     */
    public function testFullConfigLoad()
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($this->getFullYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertTrue($container->hasDefinition('snc_redis.logger'));
        $this->assertTrue($container->hasDefinition('snc_redis.data_collector'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.default_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.default'));
        $this->assertTrue($container->hasAlias('snc_redis.default_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.cache_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.cache_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.cache_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.cache'));
        $this->assertTrue($container->hasAlias('snc_redis.cache_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.monolog_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.monolog_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.monolog_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.monolog'));
        $this->assertTrue($container->hasAlias('snc_redis.monolog_client'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.cluster1_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.connection.cluster2_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.connection.cluster3_parameters'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.cluster_profile'));
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
        $this->assertTrue($container->hasDefinition('snc_redis.monolog.handler'));

        $this->assertTrue($container->hasDefinition('snc_redis.swiftmailer.spool'));
        $this->assertTrue($container->hasAlias('swiftmailer.spool.redis'));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
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
     * Test valid XML config
     */
    public function testValidXmlConfig()
    {
        $container = $this->getContainer();
        $container->registerExtension(new SncRedisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config'));
        $loader->load('valid.xml');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidXmlConfig()
    {
        $container = $this->getContainer();
        $container->registerExtension(new SncRedisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config'));
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
        $this->assertEquals(new RedisDsn('redis://test'), current($config['clients']['default']['dsns']));
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
        $this->assertEquals('snc_redis.connection.master_parameters', (string) $parameters[0]);
        $masterParameters = $container->getDefinition((string) $parameters[0])->getArgument(0);
        $this->assertTrue($masterParameters['replication']);
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
            prefix: snc:
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
            cluster: Snc\RedisBundle\Client\Predis\Connection\PredisCluster
            replication: false
session:
    client: default
    prefix: foo
    ttl: 1440
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
