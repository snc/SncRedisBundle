<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\Functional\App;

use Snc\RedisBundle\Tests\Functional\App\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Kernel
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
abstract class AbstractKernel extends BaseKernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Snc\RedisBundle\SncRedisBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config.yaml');

        // Since symfony/framework-bundle 5.3: Not setting the "framework.session.storage_factory_id" configuration option
        // is deprecated, it will replace the "framework.session.storage_id" configuration option in version 6.0.
        if (self::VERSION_ID >= 50300) {
            $container->loadFromExtension('framework', [
                'session' => [
                    'storage_factory_id' => 'session.storage.factory.mock_file',
                ],
                'router' => [
                    'utf8' => false,
                ],
            ]);
        } else {
            $container->loadFromExtension('framework', [
                'session' => [
                    'storage_id' => 'session.storage.mock_file',
                ]
            ]);
        }
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getRootDir(): string
    {
        return __DIR__ . '/var';
    }
}

// RouteCollectionBuilder is deprecated since symfony/routing 5.1
if (AbstractKernel::VERSION_ID >= 50100) {
    class Kernel extends AbstractKernel {
        protected function configureRoutes(RoutingConfigurator $routes): void
        {
            $controller = Controller::class;

            $routes->add('home', '/')->controller("{$controller}::home");
            $routes->add('create_user', '/user/create')->controller("{$controller}::createUser");
            $routes->add('view_user', '/user/view')->controller("{$controller}::viewUser");
        }
    }
} else {
    class Kernel extends AbstractKernel {
        protected function configureRoutes(RouteCollectionBuilder $routes)
        {
            $controller = Controller::class;

            $routes->add('/', "{$controller}::home");
            $routes->add('/user/create', "{$controller}::createUser");
            $routes->add('/user/view', "{$controller}::viewUser");
        }
    }
}

