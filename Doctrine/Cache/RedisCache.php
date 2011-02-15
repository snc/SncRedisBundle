<?php

namespace Snc\RedisBundle\Doctrine\Cache;

use Doctrine\Common\Cache\AbstractCache;

/**
 * Redis cache driver.
 *
 * @link    http://github.com/justinrainbow/
 * @author  Justin Rainbow <justin.rainbow@gmail.com>
 * @author  Henrik Westphal <henrik.westphal@gmail.com>
 */
class RedisCache extends AbstractCache
{
    /**
     * @var \Predis\Client
     */
    private $_redis;

    /**
     * @var string The namespace to prefix all cache ids with
     */
    private $_namespace = null;

    /**
     * @var boolean
     */
    private $_supportsSetExpire = false;

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
     * Gets the redis instance used by the cache.
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
    public function setNamespace($namespace)
    {
        $this->_namespace = $namespace;
        parent::setNamespace($namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function getIds()
    {
        return $this->_redis->keys($this->_namespace . '*');
    }

    /**
     * {@inheritdoc}
     */
    protected function _doFetch($id)
    {
        return $this->_redis->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function _doContains($id)
    {
        return (bool) $this->_redis->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function _doSave($id, $data, $lifeTime = 0)
    {
        if ($this->_supportsSetExpire && 0 < $lifeTime) {
            $result = (bool) $this->_redis->setex($id, (int) $lifeTime, $data);
        } else {
            $result = (bool) $this->_redis->set($id, $data);
            if (0 < $lifeTime) {
                $result = $result && (bool) $this->_redis->expire($id, (int) $lifeTime);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function _doDelete($id)
    {
        return (bool) $this->_redis->del($id);
    }
}
