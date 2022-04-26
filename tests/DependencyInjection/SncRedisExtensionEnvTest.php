<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\DependencyInjection;

use LogicException;
use PHPUnit\Framework\TestCase;
use Redis;
use Snc\RedisBundle\DependencyInjection\SncRedisExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use function array_key_exists;
use function sys_get_temp_dir;

class SncRedisExtensionEnvTest extends TestCase
{
    public function testPredisDefaultParameterConfigLoad(): void
    {
        $container = $this->getConfiguredContainer('env_predis_minimal');

        $this->assertSame(
            ['Snc\RedisBundle\Factory\PredisParametersFactory', 'create'],
            $container->findDefinition('snc_redis.connection.default_parameters.default')->getFactory()
        );
    }

    public function testPhpredisDefaultParameterConfig(): void
    {
        $container = $this->getConfiguredContainer('env_phpredis_minimal');

        $clientDefinition = $container->findDefinition('snc_redis.default');

        $this->assertSame('Redis', $clientDefinition->getClass());
        $this->assertSame('Redis', $clientDefinition->getArgument(0));
        $this->assertStringContainsString('REDIS_URL', $clientDefinition->getArgument(1)[0]);
        $this->assertSame('default', $clientDefinition->getArgument(3));

        $this->assertSame(
            [
                'connection_async' => false,
                'connection_persistent' => false,
                'connection_timeout' => 5,
                'read_write_timeout' => null,
                'iterable_multibulk' => false,
                'throw_errors' => true,
                'serialization' => 'default',
                'profile' => 'default',
                'cluster' => null,
                'prefix' => null,
                'service' => null,
            ],
            $clientDefinition->getArgument(2)
        );
    }

    public function testPhpredisFullConfig(): void
    {
        $container = $this->getConfiguredContainer('env_phpredis_full');

        $clientDefinition = $container->findDefinition('snc_redis.alias_test');

        $this->assertSame(Redis::class, $clientDefinition->getClass());
        $this->assertSame(Redis::class, $clientDefinition->getArgument(0));
        $this->assertStringContainsString('TEST_URL_2', $clientDefinition->getArgument(1)[0]);
        $this->assertSame('alias_test', $clientDefinition->getArgument(3));
        $this->assertSame(
            [
                'connection_timeout' => 10,
                'connection_persistent' => true,
                'prefix' => 'totoprofix',
                'serialization' => 'php',
                'connection_async' => false,
                'read_write_timeout' => null,
                'iterable_multibulk' => false,
                'throw_errors' => true,
                'profile' => 'default',
                'cluster' => null,
                'service' => null,
            ],
            $clientDefinition->getArgument(2)
        );
        $this->assertTrue($clientDefinition->getArgument(4));
    }

    public function testPhpredisWithAclConfig(): void
    {
        $container = $this->getConfiguredContainer('env_phpredis_with_acl');

        $clientDefinition = $container->findDefinition('snc_redis.acl_client');

        $this->assertSame('Redis', $clientDefinition->getClass());
        $this->assertSame('Redis', $clientDefinition->getArgument(0));
        $this->assertStringContainsString('TEST_URL_2', $clientDefinition->getArgument(1)[0]);
        $this->assertSame('acl_client', $clientDefinition->getArgument(3));

        $this->assertEquals(
            [
                'cluster' => null,
                'connection_async' => false,
                'connection_persistent' => true,
                'connection_timeout' => 10,
                'iterable_multibulk' => false,
                'parameters' => [
                    'username' => 'snc_user',
                    'password' => 'snc_password',
                    'database' => null,
                    'logging' => false,
                ],
                'prefix' => null,
                'profile' => 'default',
                'read_write_timeout' => null,
                'serialization' => 'php',
                'service' => null,
                'throw_errors' => true,
            ],
            $clientDefinition->getArgument(2)
        );
    }

    public function testProfileOption(): void
    {
        $container = $this->getConfiguredContainer('env_predis_profile');

        $this->assertTrue($container->hasDefinition('snc_redis.client.default_profile'));
        $this->assertSame('Predis\Profile\RedisVersion260', $container->getDefinition('snc_redis.client.default_profile')->getClass());
    }

    public function testClusterOption(): void
    {
        $container = $this->getConfiguredContainer('env_predis_cluster');

        $options = $container->getDefinition('snc_redis.client.default_options')->getArgument(0);
        $this->assertEquals('redis', $options['cluster']);
        $this->assertFalse(array_key_exists('replication', $options));

        $parameters = $container->getDefinition('snc_redis.default')->getArgument(0);
        $this->assertEquals('snc_redis.connection.default1_parameters.default', (string) $parameters[0]);
        $this->assertEquals('snc_redis.connection.default2_parameters.default', (string) $parameters[1]);

        $this->assertIsArray($container->findTaggedServiceIds('snc_redis.client'));
        $this->assertEquals(['snc_redis.default' => [['alias' => 'default']]], $container->findTaggedServiceIds('snc_redis.client'));
    }

    public function testPhpRedisClusterOption(): void
    {
        $container        = $this->getConfiguredContainer('env_phpredis_cluster');
        $clientDefinition = $container->findDefinition('snc_redis.phprediscluster');

        $this->assertSame('RedisCluster', $clientDefinition->getClass());
        $this->assertSame('RedisCluster', $clientDefinition->getArgument(0));
        $this->assertStringContainsString('REDIS_URL_1', $clientDefinition->getArgument(1)[0]);
        $this->assertSame('phprediscluster', $clientDefinition->getArgument(3));
        $this->assertFalse($clientDefinition->getArgument(4));

        $this->assertSame(
            [
                'cluster' => true,
                'connection_async' => false,
                'connection_persistent' => false,
                'connection_timeout' => 5,
                'read_write_timeout' => null,
                'iterable_multibulk' => false,
                'throw_errors' => true,
                'serialization' => 'default',
                'profile' => 'default',
                'prefix' => null,
                'service' => null,
            ],
            $clientDefinition->getArgument(2)
        );
    }

    public function testPhpRedisClusterOptionMultipleDsn(): void
    {
        $container        = $this->getConfiguredContainer('env_phpredis_cluster_multiple_dsn');
        $clientDefinition = $container->findDefinition('snc_redis.phprediscluster');

        $this->assertSame('RedisCluster', $clientDefinition->getClass());
        $this->assertSame('RedisCluster', $clientDefinition->getArgument(0));
        $this->assertStringContainsString('REDIS_URL_1', $clientDefinition->getArgument(1)[0]);
        $this->assertStringContainsString('REDIS_URL_2', $clientDefinition->getArgument(1)[1]);
        $this->assertStringContainsString('REDIS_URL_3', $clientDefinition->getArgument(1)[2]);
        $this->assertSame('phprediscluster', $clientDefinition->getArgument(3));

        $this->assertSame(
            [
                'cluster' => true,
                'read_write_timeout' => 1.5,
                'connection_timeout' => 1.5,
                'connection_persistent' => true,
                'connection_async' => false,
                'iterable_multibulk' => false,
                'throw_errors' => true,
                'serialization' => 'default',
                'profile' => 'default',
                'prefix' => null,
                'service' => null,
            ],
            $clientDefinition->getArgument(2)
        );
    }

    public function testPhpRedisArrayIsNotSupported(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('RedisArray is not supported yet');

        $this->getConfiguredContainer('env_phpredis_array_not_supported');
    }

    private function getConfiguredContainer(string $file): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.root_dir', __DIR__ . '/../../');

        $container->registerExtension(new SncRedisExtension());

        $locator = new FileLocator(__DIR__ . '/Fixtures/config/yaml');
        $loader  = new YamlFileLoader($container, $locator);
        $loader->load($file . '.yaml');

        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
