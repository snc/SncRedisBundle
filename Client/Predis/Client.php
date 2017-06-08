<?php

namespace Snc\RedisBundle\Client\Predis;

use Predis\Client as PredisClient;

/**
 * Class Client
 * @package Snc\RedisBundle\Client
 */
class Client extends PredisClient
{

    /**
     * Determines whether redis server is available or not
     * @var bool
     */
    protected static $available = true;

    /**
     * Sets redis as unavailable
     */
    public function setUnavailable()
    {
        static::$available = false;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($commandID, $arguments)
    {
        return true === static::$available ?
            parent::__call($commandID, $arguments) :
            (preg_match('/set/', $commandID) ? 'OK' : null);
    }

}