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
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new LoggingPass());
        $container->addCompilerPass(new MonologPass());
        $container->addCompilerPass(new SwiftMailerPass());
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
