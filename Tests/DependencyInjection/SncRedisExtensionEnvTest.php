<?php

namespace Snc\RedisBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\DependencyInjection\SncRedisExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Kernel;

class SncRedisExtensionEnvTest extends TestCase
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

    public function testDefaultParameterConfigLoad()
    {
        $container = $this->getConfiguredContainer('env_minimal');

        $this->assertSame(
            ['Snc\RedisBundle\Factory\EnvParametersFactory', 'create'],
            $container->findDefinition('snc_redis.connection.default_parameters.default')->getFactory()
        );
    }

    public function testProfileOption()
    {
        $container = $this->getConfiguredContainer('env_profile');

        $this->assertTrue($container->hasDefinition('snc_redis.client.default_profile'));
        $this->assertSame('Predis\Profile\RedisVersion260', $container->getDefinition('snc_redis.client.default_profile')->getClass());
    }

    public function testClusterOption()
    {
        $container = $this->getConfiguredContainer('env_cluster');

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertEquals('redis', $options['cluster']);
        $this->assertFalse(array_key_exists('replication', $options));

        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.default1_parameters.default', (string) $parameters[0]);
        $this->assertEquals('snc_redis.connection.default2_parameters.default', (string) $parameters[1]);

        $this->assertInternalType('array', $container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(array('snc_redis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('snc_redis.client'));
    }

    private function getConfiguredContainer($file)
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.bundles', array());
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.root_dir', __DIR__ . '/../../');

        $container->registerExtension(new SncRedisExtension());

        $locator = new FileLocator(__DIR__.'/Fixtures/config/yaml');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load($file.'.yaml');

        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        return $container;
    }
}
