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
     * @var \Predis\Client|\Redis
     */
    protected $_redis;

    /**
     * Sets the redis instance to use.
     *
     * @param \Predis\Client|\Redis $redis
     */
    public function setRedis($redis)
    {
        $this->_redis = $redis;
    }

    /**
     * Returns the redis instance used by the cache.
     *
     * @return \Predis\Client|\Redis
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
        $result = $this->_redis->get($id);

        // phpredis returns false on miss, predis returns null
        return (null === $result || false === $result) ? false : unserialize($result);
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
        if (0 < $lifeTime) {
            $result = $this->_redis->setex($id, (int) $lifeTime, serialize($data));
        } else {
            $result = $this->_redis->set($id, serialize($data));
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
            Cache::STATS_HITS => isset($stats['keyspace_hits']) ? $stats['keyspace_hits'] : $stats['Stats']['keyspace_hits'],
            Cache::STATS_MISSES => isset($stats['keyspace_misses']) ? $stats['keyspace_misses'] : $stats['Stats']['keyspace_misses'],
            Cache::STATS_UPTIME => isset($stats['uptime_in_seconds']) ? $stats['uptime_in_seconds'] : $stats['Server']['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE => isset($stats['used_memory']) ? $stats['used_memory'] : $stats['Memory']['used_memory'],
            Cache::STATS_MEMORY_AVAILIABLE => null,
        );
    }
}
