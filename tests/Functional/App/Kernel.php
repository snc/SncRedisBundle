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

use ReflectionObject;
use Snc\RedisBundle\SncRedisBundle;
use Snc\RedisBundle\Tests\Functional\App\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use function assert;
use function dirname;

class Kernel extends BaseKernel
{
    public const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /** @inheritdoc */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new WebProfilerBundle(),
            new TwigBundle(),
            new SncRedisBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config.yaml');

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                    'utf8' => true,
                ],
            ]);

            // Since symfony/framework-bundle 5.3: Not setting the "framework.session.storage_factory_id" configuration option
            // is deprecated, it will replace the "framework.session.storage_id" configuration option in version 6.0.
            if (self::VERSION_ID >= 50300) {
                $container->loadFromExtension('framework', [
                    'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
                ]);
            }

            $container->register('kernel', static::class)
                ->addTag('routing.route_loader')
                ->setAutoconfigured(true)
                ->setSynthetic(true)
                ->setPublic(true);
        });
    }

    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $file         = (new ReflectionObject($this))->getFileName();
        $kernelLoader = $loader->getResolver()->resolve($file, 'php');
        assert($kernelLoader instanceof PhpFileLoader);
        $kernelLoader->setCurrentDir(dirname($file));

        $collection = new RouteCollection();
        $collection->add('home', new Route('/', ['_controller' => Controller::class]));

        return $collection;
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
