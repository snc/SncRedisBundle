<?php

namespace Bundle\RedisBundle;

use Bundle\RedisBundle\DependencyInjection\RedisExtension;
use Symfony\Framework\Bundle\Bundle;
use Symfony\Components\DependencyInjection\ContainerInterface;
use Symfony\Components\DependencyInjection\Loader\Loader;

class RedisBundle extends Bundle
{
    /**
     * Customizes the Container instance.
     *
     * @param Symfony\Components\DependencyInjection\ContainerInterface $container A ContainerInterface instance
     */
    public function buildContainer(ContainerInterface $container)
    {
        Loader::registerExtension(new RedisExtension());
    }
}
