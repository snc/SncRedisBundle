<?php

namespace Snc\RedisBundle\DependencyInjection\Configuration;

interface RedisDsnInterface
{
    /**
     * @return bool
     */
    public function isValid();

    /**
     * @return string
     */
    public function getAlias();
}
