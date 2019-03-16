<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle;

use Snc\RedisBundle\Client\Phpredis\Client;
use Snc\RedisBundle\DependencyInjection\Compiler\ClientLocatorPass;
use Snc\RedisBundle\DependencyInjection\Compiler\LoggingPass;
use Snc\RedisBundle\DependencyInjection\Compiler\MonologPass;
use Snc\RedisBundle\DependencyInjection\Compiler\SwiftMailerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * SncRedisBundle
 */
class SncRedisBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        parent::boot();

        if ($this->container->getParameter('snc_redis.phpredis_client.class') === Client::class) {
            if (!class_exists(Client::class)) {
                if (!file_exists($file = $this->container->getParameter('kernel.cache_dir').'/snc_phpredis_client.php')) {
                    throw new \LogicException(sprintf('You must warmup the cache before using the %s class', Client::class));
                }

                require $file;
            }
        }
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new LoggingPass());
        $container->addCompilerPass(new MonologPass());
        $container->addCompilerPass(new SwiftMailerPass());
        $container->addCompilerPass(new ClientLocatorPass());
    }

    /**
     * {@inheritDoc}
     */
    public function shutdown()
    {
        // Close session handler connection to avoid using up all available connection slots in tests
        if ($this->container->has('snc_redis.session.handler')) {
            if (!method_exists($this->container, 'initialized') || $this->container->initialized('snc_redis.session.handler')) {
                $this->container->get('snc_redis.session.handler')->close();
            }
        }
    }
}
