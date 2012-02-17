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

/**
 * Redis cache class using a SET to manage cache ids
 *
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 */
class RedisSetCache extends RedisCache
{
    /**
     * @var string An id to store the cache ids
     */
    private $_setKey = 'doctrine_cache_ids';

    /**
     * Sets the SET key
     *
     * @param $key
     */
    public function setSetKey($key)
    {
        $this->_setKey = $key;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        if (parent::contains($id)) {
            return $this->_redis->sismember($this->getNamespacedSetKey(), $id);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = false)
    {
        $result = parent::save($id, $data, $lifeTime);

        if ($result) {
            // SADD may return 0 if the key is already part of the set
            $this->_redis->sadd($this->getNamespacedSetKey(), $id);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $result = parent::doDelete($id);

        if ($result) {
            // SREM may return 0 if the key is not part of the set
            $this->_redis->srem($this->getNamespacedSetKey(), $id);
        }

        return $result;
    }

    /**
     * Returns the SET key prefixed by the namespace
     *
     * @return string
     */
    private function getNamespacedSetKey()
    {
        return $this->getNamespace() . ':' . $this->_setKey;
    }
}
