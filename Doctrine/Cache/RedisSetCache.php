<?php

namespace Snc\RedisBundle\Doctrine\Cache;

/**
 * Redis cache class using a SET to manage cache ids
 *
 * @author  Henrik Westphal <henrik.westphal@gmail.com>
 */
class RedisSetCache extends RedisCache
{
    /**
     * @var string An id to store the cache ids
     */
    private $_setKey = 'doctrine_cache_ids';

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        $result = parent::contains($id);

        if ($result) {
            $result = $this->_redis->sismember($this->_getNamespacedId($this->_setKey), $this->_getNamespacedId($id));
        }

        return (bool) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $result = parent::save($id, $data, $lifeTime);

        if ($result) {
            $this->_redis->sadd($this->_getNamespacedId($this->_setKey), $this->_getNamespacedId($id));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $ids = $this->getIds();
        $ids[] = $this->_getNamespacedId($this->_setKey);

        $this->_redis->del($ids);

        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function getIds($prefix = null)
    {
        if ($prefix) {
            $prefix = $this->_getNamespacedId($prefix);
            $result = array();
            foreach ($this->_redis->smembers($this->_getNamespacedId($this->_setKey)) as $id) {
                if (0 === strpos($id, $prefix)) {
                    $result[] = $id;
                }
            }
            return $result;
        } else {
            return $this->_redis->smembers($this->_getNamespacedId($this->_setKey));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _doDelete($id)
    {
        $result = parent::_doDelete($id);

        if ($result) {
            if (is_array($id)) {
                $key = $this->_getNamespacedId($this->_setKey);
                $this->_redis->pipeline(function($pipe) use ($key, $id) {
                    foreach($id as $value) {
                        $pipe->srem($key, $value);
                    }
                });
            } else {
                $this->_redis->srem($this->_getNamespacedId($this->_setKey), $id);
            }
        }

        return $result;
    }
}
