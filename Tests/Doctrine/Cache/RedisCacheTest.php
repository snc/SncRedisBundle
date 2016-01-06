<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Tests\Doctrine\Cache;

use Doctrine\Common\Cache\PredisCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Tests\Common\Cache\CacheTest;

/**
 * RedisCacheTest
 */
class RedisCacheTest extends CacheTest
{
    protected $_redis;
    protected $_namespace;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $config = 'tcp://127.0.0.1:6379';

        if (class_exists('\Predis\Client')) {
            $this->_redis = new \Predis\Client($config);
        } elseif (class_exists('\Redis')) {
            $this->_redis = new \Redis();
            $this->_redis->connect($config);
        } else {
            $this->markTestSkipped(sprintf('The %s requires the predis library or phpredis extension.', __CLASS__));
        }

        if (null !== $this->_redis) {
            try {
                $ok = $this->_redis->ping();
            } catch (\Exception $e) {
                $ok = false;
            }
            if (!$ok) {
                $this->markTestSkipped(sprintf('The %s requires a redis instance listening on %s.', __CLASS__, $config));
            }
        }

        // Use a unique namespace
        $this->_namespace = uniqid(__METHOD__, true);
    }

    protected function _getCacheDriver()
    {
        // $driver = new RedisCache();
        if (class_exists('\Predis\Client')) {
            $driver = new PredisCache($this->_redis);
        } elseif (class_exists('\Redis')) {
            $driver = new RedisCache();
            $driver->setRedis($this->_redis);
        } else {
            $this->markTestSkipped(sprintf('The %s requires the predis library or phpredis extension.', __CLASS__));
        }
        $driver->setNamespace($this->_namespace);

        return $driver;
    }
}
