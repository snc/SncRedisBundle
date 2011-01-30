<?php

namespace Bundle\RedisBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * RedisBundle
 */
class RedisBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return strtr(__DIR__, '\\', '/');
    }
}
