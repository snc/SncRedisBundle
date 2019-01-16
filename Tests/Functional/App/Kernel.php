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
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Kernel
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Snc\RedisBundle\SncRedisBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config.yaml');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $controller = Controller::class;

        $routes->add('/', "{$controller}:home");
        $routes->add('/user/create', "{$controller}:createUser");
        $routes->add('/user/view', "{$controller}:viewUser");
    }

    public function getProjectDir()
    {
        return __DIR__;
    }

    public function getRootDir()
    {
        return __DIR__ . '/var';
    }

}
