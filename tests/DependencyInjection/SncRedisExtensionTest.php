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

namespace Snc\RedisBundle\Tests\DependencyInjection;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\DependencyInjection\Configuration\Configuration;
use Snc\RedisBundle\DependencyInjection\SncRedisExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Parser;

use function array_key_exists;
use function count;
use function current;
use function sys_get_temp_dir;

/**
 * SncRedisExtensionTest
 */
class SncRedisExtensionTest extends TestCase
{
    /** @return array<array{0: string, 1: string}> */
    public static function parameterValues(): array
    {
        return [
            ['snc_redis.client.class', 'Predis\Client'],
            ['snc_redis.client_options.class', 'Predis\Configuration\Options'],
            ['snc_redis.connection_parameters.class', 'Predis\Connection\Parameters'],
            ['snc_redis.connection_factory.class', 'Snc\RedisBundle\Client\Predis\Connection\ConnectionFactory'],
            ['snc_redis.connection_wrapper.class', 'Snc\RedisBundle\Client\Predis\Connection\ConnectionWrapper'],
            ['snc_redis.logger.class', 'Snc\RedisBundle\Logger\RedisLogger'],
            ['snc_redis.data_collector.class', 'Snc\RedisBundle\DataCollector\RedisDataCollector'],
            ['snc_redis.monolog_handler.class', 'Monolog\Handler\RedisHandler'],
        ];
    }

    public function testEmptyConfigLoad(): void
    {
        $extension = new SncRedisExtension();
        $config    = [];
        $extension->load([$config], $container = $this->getContainer());
        $this->assertArrayNotHasKey('snc_redis.client', $container->getDefinitions());
    }

    /**
     * @param string $name     Name
     * @param string $expected Expected value
     *
     * @dataProvider parameterValues
     */
    public function testDefaultParameterConfigLoad(string $name, string $expected): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $this->assertEquals($expected, $container->getParameter($name));
    }

    /**
     * @param string $name     Name
     * @param string $expected Expected value
     *
     * @dataProvider parameterValues
     */
    public function testNoClientsConfigLoad(string $name, string $expected): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getNoClientYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $this->assertEquals($expected, $container->getParameter($name));
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testDefaultClientTaggedServicesConfigLoad(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $this->assertIsArray($container->findTaggedServiceIds('snc_redis.client'));
        $this->assertCount(1, $container->findTaggedServiceIds('snc_redis.client'), 'Minimal Yaml should have tagged 1 client');
    }

    /**
     * Test loading of minimal config
     */
    public function testMinimalConfigLoad(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load([$config], $container = $this->getContainer());
        $this->assertTrue($container->hasDefinition('snc_redis.logger'));
        $this->assertTrue($container->hasDefinition('snc_redis.data_collector'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.default_parameters.default'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.default'));
        $this->assertFalse($container->hasAlias('snc_redis.default_client'));
        $this->assertIsArray($container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(['snc_redis.default' => [['alias' => 'default']]], $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * @group legacy
     *
     * Test loading of full config
     */
    public function testFullConfigLoad(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getFullYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

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

        $this->assertTrue($container->hasDefinition('snc_redis.monolog'));
        $this->assertFalse($container->hasAlias('snc_redis.monolog_client'));
        $this->assertTrue($container->hasDefinition('snc_redis.monolog.handler'));

        $this->assertIsArray($container->findTaggedServiceIds('snc_redis.client'));
        $this->assertGreaterThanOrEqual(4, count($container->findTaggedServiceIds('snc_redis.client')), 'expected at least 4 tagged clients');

        $tags = $container->findTaggedServiceIds('snc_redis.client');
        $this->assertArrayHasKey('snc_redis.default', $tags);
        $this->assertArrayHasKey('snc_redis.cache', $tags);
        $this->assertArrayHasKey('snc_redis.monolog', $tags);
        $this->assertArrayHasKey('snc_redis.cluster', $tags);
        $this->assertEquals([['alias' => 'cache']], $tags['snc_redis.cache']);
        $this->assertEquals([['alias' => 'cluster']], $tags['snc_redis.cluster']);
    }

    public function testInvalidMonologConfigLoad(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You have to disable logging for the client');

        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getInvalidMonologYamlConfig());
        $extension->load([$config], $this->getContainer());
    }

    /**
     * Test the monolog formatter option
     */
    public function testMonologFormatterOption(): void
    {
        $container = $this->getContainer();
        //Create a fake formatter definition
        $container->setDefinition('my_monolog_formatter', new Definition('Monolog\\Formatter\\LogstashFormatter', ['symfony']));
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getMonologFormatterOptionYamlConfig());
        $extension->load([$config], $container);

        $loggerDefinition = $container->getDefinition('snc_redis.monolog.handler');
        $calls            = $loggerDefinition->getMethodCalls();
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
    public function testClientProfileOption(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getFullYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $profileDefinition = $container->getDefinition('snc_redis.client.default_profile');
        $options           = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);

        $this->assertSame((float) 2, $config['clients']['default']['options']['profile'], 'Profile version 2.0 was parsed as float');
        $this->assertSame('Predis\\Profile\\RedisVersion200', $profileDefinition->getClass(), 'Profile definition is instance of Predis\\Profile\\RedisVersion200');

        $this->assertSame('snc:', $options['prefix'], 'Prefix option was allowed');
    }

    /**
     * Test multiple clients both containing "master" dsn aliases
     */
    public function testMultipleClientMaster(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getMultipleReplicationYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

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
    public function testValidXmlConfig(): void
    {
        $container = $this->getContainer();
        $container->registerExtension(new SncRedisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config/xml'));
        $loader->load('valid.xml');
    }

    public function testInvalidXmlConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $container = $this->getContainer();
        $container->registerExtension(new SncRedisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config/xml'));
        $loader->load('invalid.xml');
    }

    /**
     * Test config merging
     */
    public function testConfigurationMerging(): void
    {
        $configuration = new Configuration(true);
        $configs       = [$this->parseYaml($this->getMergeConfig1()), $this->parseYaml($this->getMergeConfig2())];
        $processor     = new Processor();
        $config        = $processor->processConfiguration($configuration, $configs);
        $this->assertCount(1, $config['clients']['default']['dsns']);
        $this->assertEquals('redis://test', current($config['clients']['default']['dsns']));
    }

    /**
     * Test valid config of the replication option
     */
    public function testClientReplicationOption(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getReplicationYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertTrue($options['replication']);
        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.master_parameters.default', (string) $parameters[0]);
        $masterParameters = $container->getDefinition((string) $parameters[0])->getArgument(0);
        $this->assertTrue($masterParameters['replication']);

        $this->assertIsArray($container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(['snc_redis.default' => [['alias' => 'default']]], $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * Test valid config of the serialization option
     */
    public function testClientSerializationOption(): void
    {
         $extension = new SncRedisExtension();
         $config    = $this->parseYaml($this->getSerializationYamlConfig());
         $extension->load([$config], $container = $this->getContainer());
         $options          = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
         $parameters       = $container->getDefinition('snc_redis.default')->getArgument(0);
         $masterParameters = $container->getDefinition((string) $parameters[0])->getArgument(0);
         $this->assertSame($options['serialization'], $masterParameters['serialization']);
    }

    /**
     * Test valid config of the single host sentinel replication option
     */
    public function testSingleSentinelOption(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getSingleSentinelYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertEquals('sentinel', $options['replication']);
        $this->assertEquals('mymaster', $options['service']);
        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.default_parameters.default', (string) $parameters[0]);
        $masterParameters = $container->getDefinition((string) $parameters[0])->getArgument(0);
        $this->assertEquals('sentinel', $masterParameters['replication']);
        $this->assertEquals('mymaster', $masterParameters['service']);
        $this->assertIsArray($masterParameters['parameters']);
        $this->assertEquals('1', $masterParameters['parameters']['database']);
        $this->assertEquals('pass', $masterParameters['parameters']['password']);

        $this->assertIsArray($container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(['snc_redis.default' => [['alias' => 'default']]], $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * Test valid config of the sentinel replication option
     */
    public function testSentinelOption(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getSentinelYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertEquals('sentinel', $options['replication']);
        $this->assertEquals('mymaster', $options['service']);
        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.master_parameters.default', (string) $parameters[0]);
        $masterParameters = $container->getDefinition((string) $parameters[0])->getArgument(0);
        $this->assertEquals('sentinel', $masterParameters['replication']);
        $this->assertEquals('mymaster', $masterParameters['service']);
        $this->assertIsArray($masterParameters['parameters']);
        $this->assertEquals('1', $masterParameters['parameters']['database']);
        $this->assertEquals('pass', $masterParameters['parameters']['password']);

        $this->assertIsArray($container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(['snc_redis.default' => [['alias' => 'default']]], $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * Test valid config of the cluster option
     */
    public function testClusterOption(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getClusterYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertEquals('redis', $options['cluster']);
        $this->assertFalse(array_key_exists('replication', $options));

        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.default1_parameters.default', (string) $parameters[0]);
        $this->assertEquals('snc_redis.connection.default2_parameters.default', (string) $parameters[1]);

        $this->assertIsArray($container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(['snc_redis.default' => [['alias' => 'default']]], $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * Test provided options are respected
     */
    public function testPhpRedisParameters(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getPhpRedisYamlConfigWithParameters());
        $extension->load([$config], $container = $this->getContainer());

        $defaultParameters = $container->getDefinition('snc_redis.default');

        $this->assertSame(1, $defaultParameters->getArgument(2)['parameters']['database']);
        $this->assertSame('sncredis', $defaultParameters->getArgument(2)['parameters']['password']);

        $redis = $container->get('snc_redis.default');

        $this->assertSame(1, $redis->getDBNum());
        $this->assertSame('sncredis', $redis->getAuth());
    }

    /**
     * Test parameters provided at DSN overrides the provided options
     */
    public function testPhpRedisDuplicatedParameters(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getPhpRedisYamlConfigWithDuplicatedParameters());
        $extension->load([$config], $container = $this->getContainer());

        $defaultParameters = $container->getDefinition('snc_redis.default');

        $this->assertSame(2, $defaultParameters->getArgument(2)['parameters']['database']);
        $this->assertSame('otherpassword', $defaultParameters->getArgument(2)['parameters']['password']);

        $redis = $container->get('snc_redis.default');

        $this->assertSame(1, $redis->getDBNum());
        $this->assertSame('sncredis', $redis->getAuth());
    }

    /**
     * Test minimal RedisCluster configuration
     */
    public function testPhpRedisClusterParameters(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getPhpRedisClusterYamlMinimalConfig());
        $extension->load([$config], $container = $this->getContainer());

        $redis = $container->get('snc_redis.default');

        $this->assertInstanceOf('\RedisCluster', $redis);
    }

    /**
     * Test minimal Redis configuration with ACL
     */
    public function testPhpRedisWithACLParameters(): void
    {
        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getPhpRedisWithACLYamlMinimalConfig());
        $extension->load([$config], $container = $this->getContainer());

        $redis = $container->get('snc_redis.default');

        $this->assertInstanceOf('\Redis', $redis);

        $redis->set('test_key', 'test_value');
        $this->assertEquals('test_value', $redis->get('test_key'));
    }

    /**
     * Test minimal Redis configuration with ACL and an invalid username/password
     */
    public function testPhpRedisWithInvalidACLParameters(): void
    {
        $this->expectException('\RedisException');
        $this->expectExceptionMessageMatches('/WRONGPASS invalid username/');

        $extension = new SncRedisExtension();
        $config    = $this->parseYaml($this->getPhpRedisWithInvalidACLYamlMinimalConfig());
        $extension->load([$config], $container = $this->getContainer());

        $container->get('snc_redis.default');
    }

    /** @return mixed[] */
    private function parseYaml(string $yaml): array
    {
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function getSerializationYamlConfig(): string
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

    private function getNoClientYamlConfig(): string
    {
        return <<<'EOF'
clients:
EOF;
    }

    private function getMinimalYamlConfig(): string
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        dsn: redis://localhost
EOF;
    }

    private function getFullYamlConfig(): string
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
monolog:
    client: monolog
    key: monolog
EOF;
    }

    private function getInvalidMonologYamlConfig(): string
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

    private function getMonologFormatterOptionYamlConfig(): string
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

    private function getMergeConfig1(): string
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

    private function getMergeConfig2(): string
    {
        return <<<'EOF'
clients:
    default:
        dsn: redis://test
EOF;
    }

    private function getReplicationYamlConfig(): string
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

    private function getSingleSentinelYamlConfig(): string
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

    private function getSentinelYamlConfig(): string
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

    private function getClusterYamlConfig(): string
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

    private function getMultipleReplicationYamlConfig(): string
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

    private function getPhpRedisYamlConfigWithParameters(): string
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
                password: sncredis
EOF;
    }

    private function getPhpRedisYamlConfigWithDuplicatedParameters(): string
    {
        return <<<'EOF'
clients:
    default:
        type: phpredis
        alias: default
        dsn: redis://redis:sncredis@localhost/1
        options:
            parameters:
                database: 2
                password: otherpassword
EOF;
    }

    private function getPhpRedisClusterYamlMinimalConfig(): string
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

    private function getPhpRedisWithACLYamlMinimalConfig(): string
    {
        return <<<YAML
clients:
    default:
        type: phpredis
        alias: default
        dsn: ["redis://localhost:8000/0"]
        options:
            parameters:
                username: snc_redis
                password: snc_password
            
YAML;
    }

    private function getPhpRedisWithInvalidACLYamlMinimalConfig(): string
    {
        return <<<YAML
clients:
    default:
        type: phpredis
        alias: default
        dsn: ["redis://localhost:8000/0"]
        options:
            parameters:
                username: user
                password: password
            
YAML;
    }

    private function getContainer(): ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug'       => false,
            'kernel.bundles'     => [],
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir'    => __DIR__ . '/../../', // src dir
        ]));
    }
}
