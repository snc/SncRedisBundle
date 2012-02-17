<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Doctrine\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;

/**
 * Redis cache class
 *
 * @author Justin Rainbow <justin.rainbow@gmail.com>
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 */
class RedisCache extends CacheProvider
{
    /**
     * @var \Predis\Client
     */
    protected $_redis;

    /**
     * @var boolean
     */
    protected $_supportsSetExpire = false;

    /**
     * Sets the redis instance to use.
     *
     * @param \Predis\Client $redis
     */
    public function setRedis(\Predis\Client $redis)
    {
        $this->_redis = $redis;
        $this->_supportsSetExpire = $redis->getProfile()->supportsCommand('setex');
    }

    /**
     * Returns the redis instance used by the cache.
     *
     * @return \Predis\Client
     */
    public function getRedis()
    {
        return $this->_redis;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        return $this->_redis->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return (bool) $this->_redis->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = false)
    {
        if ($this->_supportsSetExpire && 0 < $lifeTime) {
            $result = $this->_redis->setex($id, (int) $lifeTime, $data);
        } else {
            $result = $this->_redis->set($id, $data);
            if ($result && 0 < $lifeTime) {
                $result = $this->_redis->expire($id, (int) $lifeTime);
            }
        }

        return (bool) $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return (bool) $this->_redis->del($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return (bool) $this->_redis->flushdb();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $stats = $this->_redis->info();

        return array(
            Cache::STATS_HITS => $stats['keyspace_hits'],
            Cache::STATS_MISSES => $stats['keyspace_misses'],
            Cache::STATS_UPTIME => $stats['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE => $stats['used_memory'],
            Cache::STATS_MEMORY_AVAILIABLE => null,
        );
    }
}
