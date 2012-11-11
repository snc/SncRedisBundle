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

/**
 * Redis cache class
 *
 * @author Justin Rainbow <justin.rainbow@gmail.com>
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 */
class RedisCache implements Cache
{
    /**
     * @var \Predis\Client|\Redis
     */
    protected $_redis;

    /**
     * @var string The namespace to prefix all cache ids with
     */
    protected $_namespace = null;

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
     * Sets the string to prefix all cache ids with.
     *
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->_namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $result = $this->_redis->get($this->_getNamespacedId($id));

        return null === $result ? false : unserialize($result);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return (bool) $this->_redis->exists($this->_getNamespacedId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $id = $this->_getNamespacedId($id);
        $data = serialize($data);

        if (0 < $lifeTime) {
            $result = $this->_redis->setex($id, (int) $lifeTime, $data);
        } else {
            $result = $this->_redis->set($id, $data);
        }

        return (bool) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if (false !== strpos($id, '*')) {
            return $this->deleteByRegex('/' . str_replace('*', '.*', $id) . '/');
        }

        return $this->_doDelete($this->_getNamespacedId($id));
    }

    /**
     * Deletes all cache entries.
     *
     * @return array An array of deleted cache ids
     */
    public function deleteAll()
    {
        $ids = $this->getIds();

        if (count($ids) > 0) {
            $this->_doDelete($ids);
        }

        return $ids;
    }

    /**
     * Deletes all cache entries matching the given regular expression.
     *
     * @param string $regex
     *
     * @return array An array of deleted cache ids
     */
    public function deleteByRegex($regex)
    {
        $deleted = array();

        $ids = $this->getIds();

        foreach ($ids as $id) {
            if (preg_match($regex, $id)) {
                $deleted[] = $id;
            }
        }

        if (count($deleted) > 0) {
            $this->_doDelete($deleted);
        }

        return $deleted;
    }

    /**
     * Deletes all cache entries beginning with the given string.
     *
     * @param string $prefix
     *
     * @return array An array of deleted cache ids
     */
    public function deleteByPrefix($prefix)
    {
        $deleted = $this->getIds($prefix);

        if (count($deleted) > 0) {
            $this->_doDelete($deleted);
        }

        return $deleted;
    }

    /**
     * Deletes all cache entries ending with the given string.
     *
     * @param string $suffix
     *
     * @return array An array of deleted cache ids
     */
    public function deleteBySuffix($suffix)
    {
        $deleted = array();

        $ids = $this->getIds();

        foreach ($ids as $id) {
            if ($suffix === substr($id, -1 * strlen($suffix))) {
                $deleted[] = $id;
            }
        }

        if (count($deleted) > 0) {
            $this->_doDelete($deleted);
        }

        return $deleted;
    }

    /**
     * Returns an array of cache ids.
     *
     * @param string $prefix Optional id prefix
     *
     * @return array An array of cache ids
     */
    public function getIds($prefix = null)
    {
        if ($prefix) {
            return $this->_redis->keys($this->_getNamespacedId($prefix) . '*');
        } else {
            return $this->_redis->keys($this->_getNamespacedId('*'));
        }
    }

    /**
     * Deletes one or more cache entries.
     *
     * @param string|array $id Cache id(s)
     *
     * @return boolean
     */
    protected function _doDelete($id)
    {
        return (bool) $this->_redis->del($id);
    }

    /**
     * Returns the given cache id prefixed with the namespace.
     *
     * @param string $id Cache id
     *
     * @return string Prefixes cache id
     */
    protected function _getNamespacedId($id)
    {
        if (!$this->_namespace || 0 === strpos($id, $this->_namespace)) {
            return $id;
        } else {
            return $this->_namespace . $id;
        }
    }
}
