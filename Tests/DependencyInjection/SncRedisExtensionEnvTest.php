<?php

namespace Snc\RedisBundle\Tests\DependencyInjection;

use Snc\RedisBundle\DependencyInjection\SncRedisExtension;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Parser;

/**
 * SncRedisExtensionTest
 */
class SncRedisExtensionEnvTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @see http://symfony.com/blog/new-in-symfony-3-2-runtime-environment-variables
     */
    protected function setUp()
    {
        if (version_compare(Kernel::VERSION, '3.2.0', '<')) {
            $this->markTestSkipped(
                'env() style parameters are supported from Symfony 3.2.0 onwards.'
            );
        }
    }

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
            array('snc_redis.doctrine_cache_phpredis.class', 'Doctrine\Common\Cache\RedisCache'),
            array('snc_redis.doctrine_cache_predis.class', 'Doctrine\Common\Cache\PredisCache'),
            array('snc_redis.monolog_handler.class', 'Monolog\Handler\RedisHandler'),
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
        $container = $this->getConfiguredContainer($this->getMinimalYamlConfig());

        $this->assertEquals($expected, $container->getParameter($name));
    }

    /**
     * @param string $name     Name
     * @param string $expected Expected value
     *
     * @dataProvider parameterValues
     */
    public function testDefaultClientTaggedServicesConfigLoad($name, $expected)
    {
        $container = $this->getConfiguredContainer($this->getMinimalYamlConfig());

        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertCount(1, $container->findTaggedServiceIds('snc_redis.client'), 'Minimal Yaml should have tagged 1 client');
    }

    /**
     * Test loading of minimal config
     */
    public function testMinimalConfigLoad()
    {
        $container = $this->getConfiguredContainer($this->getMinimalYamlConfig());

        $this->assertTrue($container->hasDefinition('snc_redis.logger'));
        $this->assertTrue($container->hasDefinition('snc_redis.data_collector'));

        $this->assertTrue($container->hasDefinition('snc_redis.connection.default_parameters.default'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_profile'));
        $this->assertTrue($container->hasDefinition('snc_redis.client.default_options'));
        $this->assertTrue($container->hasDefinition('snc_redis.default'));
        $this->assertTrue($container->hasAlias('snc_redis.default_client'));
        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(array('snc_redis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('snc_redis.client'));
    }

    /**
     * Test valid config of the cluster option
     */
    public function testClusterOption()
    {
        $container = $this->getConfiguredContainer($this->getClusterYamlConfig());

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertEquals('redis', $options['cluster']);
        $this->assertFalse(array_key_exists('replication', $options));

        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.default1_parameters.default', (string) $parameters[0]);
        $this->assertEquals('snc_redis.connection.default2_parameters.default', (string) $parameters[1]);

        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(array('snc_redis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('snc_redis.client'));
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
        dsn: "%env(REDIS_URL)%"
EOF;
    }

    public function getClusterYamlConfig()
    {
        return <<<'EOF'
clients:
    default:
        type: predis
        alias: default
        dsn:
            - "%env(REDIS_URL_1)%"
            - "%env(REDIS_URL_2)%"
        options:
            cluster: "redis"
EOF;
    }

    private function getContainer()
    {
        return new ContainerBuilder(new EnvPlaceholderParameterBag(array(
            'kernel.debug' => false,
            'kernel.bundles' => array(),
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../',
            'env(REDIS_URL)' => 'redis://localhost',
            'env(REDIS_URL_1)' => 'redis://localhost',
            'env(REDIS_URL_2)' => 'redis://localhost',
        )));
    }

    private function getConfiguredContainer($yaml)
    {
        $extension = new SncRedisExtension();
        $config = $this->parseYaml($yaml);

        $container = $this->getContainer();

        $container->registerExtension($extension);
        $container->prependExtensionConfig($extension->getAlias(), $config);

        $pass = new MergeExtensionConfigurationPass();
        $pass->process($container);

        return $container;
    }
}
