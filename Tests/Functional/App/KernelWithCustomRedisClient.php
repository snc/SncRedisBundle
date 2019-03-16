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

use Snc\RedisBundle\Client\Phpredis\Client;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
class KernelWithCustomRedisClient extends Kernel
{
    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        parent::configureContainer($container, $loader);

        $container->loadFromExtension('snc_redis', [
            'class' => [
                'phpredis_client' => Client::class,
            ],
        ]);
    }
}
