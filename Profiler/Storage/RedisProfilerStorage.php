<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Profiler\Storage;

use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;

/**
 * RedisProfilerStorage stores profiling information in Redis.
 *
 * This class is a reimplementation of
 * the RedisProfilerStorage class from Symfony 2.8
 *
 * @author Andrej Hudec <pulzarraider@gmail.com>
 * @author Stephane PY <py.stephane1@gmail.com>
 * @author Gijs van Lammeren <gijsvanlammeren@gmail.com>
 */
class RedisProfilerStorage implements ProfilerStorageInterface
{
    /**
     * Key prefix
     *
     * @var string
     */
    const TOKEN_PREFIX = 'sf_prof_';

    /**
     * Index token name
     *
     * @var string
     */
    const INDEX_NAME = 'index';

    const REDIS_SERIALIZER_NONE = 0;
    const REDIS_SERIALIZER_PHP = 1;

    /**
     * The redis client.
     *
     * @var \Predis\Client|\Redis
     */
    protected $redis;

    /**
     * TTL for profiler data (in seconds).
     *
     * @var int
     */
    protected $lifetime;

    /**
     * Constructor.
     *
     * @param \Predis\Client|\Redis $redis    Redis database connection
     * @param int                   $lifetime The lifetime to use for the purge
     */
    public function __construct($redis, $lifetime = 86400)
    {
        $this->redis = $redis;
        $this->lifetime = (int) $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function find($ip, $url, $limit, $method, $start = null, $end = null)
    {
        $indexName = $this->getIndexName();

        if (!$indexContent = $this->getValue($indexName, self::REDIS_SERIALIZER_NONE)) {
            return array();
        }

        $profileList = array_reverse(explode("\n", $indexContent));
        $result = array();

        foreach ($profileList as $item) {
            if ($limit === 0) {
                break;
            }

            if ($item == '') {
                continue;
            }

            $values = explode("\t", $item, 7);
            list($itemToken, $itemIp, $itemMethod, $itemUrl, $itemTime, $itemParent) = $values;
            $statusCode = isset($values[6]) ? $values[6] : null;

            $itemTime = (int) $itemTime;

            if ($ip && false === strpos($itemIp, $ip) || $url && false === strpos($itemUrl, $url) || $method && false === strpos($itemMethod, $method)) {
                continue;
            }

            if (!empty($start) && $itemTime < $start) {
                continue;
            }

            if (!empty($end) && $itemTime > $end) {
                continue;
            }

            $result[] = array(
                'token' => $itemToken,
                'ip' => $itemIp,
                'method' => $itemMethod,
                'url' => $itemUrl,
                'time' => $itemTime,
                'parent' => $itemParent,
                'status_code' => $statusCode,
            );
            --$limit;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        // delete only items from index
        $indexName = $this->getIndexName();

        $indexContent = $this->getValue($indexName, self::REDIS_SERIALIZER_NONE);

        if (!$indexContent) {
            return false;
        }

        $profileList = explode("\n", $indexContent);

        $result = array();

        foreach ($profileList as $item) {
            if ($item == '') {
                continue;
            }

            if (false !== $pos = strpos($item, "\t")) {
                $result[] = $this->getItemName(substr($item, 0, $pos));
            }
        }

        $result[] = $indexName;

        return $this->delete($result);
    }

    /**
     * {@inheritdoc}
     */
    public function read($token)
    {
        if (empty($token)) {
            return false;
        }

        $profile = $this->getValue($this->getItemName($token), self::REDIS_SERIALIZER_PHP);

        if ($profile) {
            $profile = $this->createProfileFromData($token, $profile);
        }

        return $profile;
    }

    /**
     * {@inheritdoc}
     */
    public function write(Profile $profile)
    {
        $data = array(
            'token' => $profile->getToken(),
            'parent' => $profile->getParentToken(),
            'children' => array_map(function ($p) { return $p->getToken(); }, $profile->getChildren()),
            'data' => $profile->getCollectors(),
            'ip' => $profile->getIp(),
            'method' => $profile->getMethod(),
            'url' => $profile->getUrl(),
            'time' => $profile->getTime(),
        );

        $profileIndexed = $this->getValue($this->getItemName($profile->getToken()));

        if ($this->setValue($this->getItemName($profile->getToken()), $data, $this->lifetime, self::REDIS_SERIALIZER_PHP)) {
            if (!$profileIndexed) {
                // Add to index
                $indexName = $this->getIndexName();

                $indexRow = implode("\t", array(
                    $profile->getToken(),
                    $profile->getIp(),
                    $profile->getMethod(),
                    $profile->getUrl(),
                    $profile->getTime(),
                    $profile->getParentToken(),
                    $profile->getStatusCode(),
                )) . "\n";

                return $this->appendValue($indexName, $indexRow, $this->lifetime);
            }

            return true;
        }

        return false;
    }

    /**
     * Creates a Profile.
     *
     * @param  string  $token
     * @param  array   $data
     * @param  Profile $parent
     * @return Profile
     */
    protected function createProfileFromData($token, $data, $parent = null)
    {
        $profile = new Profile($token);
        $profile->setIp($data['ip']);
        $profile->setMethod($data['method']);
        $profile->setUrl($data['url']);
        $profile->setTime($data['time']);
        $profile->setCollectors($data['data']);

        if (!$parent && $data['parent']) {
            $parent = $this->read($data['parent']);
        }

        if ($parent) {
            $profile->setParent($parent);
        }

        foreach ($data['children'] as $token) {
            if (!$token) {
                continue;
            }

            if (!$childProfileData = $this->getValue($this->getItemName($token), self::REDIS_SERIALIZER_PHP)) {
                continue;
            }

            $profile->addChild($this->createProfileFromData($token, $childProfileData, $profile));
        }

        return $profile;
    }

    /**
     * Gets the item name.
     *
     * @param string $token
     *
     * @return string
     */
    protected function getItemName($token)
    {
        $name = $this->prefixKey($token);

        if ($this->isItemNameValid($name)) {
            return $name;
        }

        return false;
    }

    /**
     * Gets the name of the index.
     *
     * @return string
     */
    protected function getIndexName()
    {
        $name = $this->prefixKey(self::INDEX_NAME);

        if ($this->isItemNameValid($name)) {
            return $name;
        }

        return false;
    }

    /**
     * Check if the item name is valid.
     *
     * @param  string            $name
     * @throws \RuntimeException
     * @return bool
     */
    protected function isItemNameValid($name)
    {
        $length = strlen($name);

        if ($length > 2147483648) {
            throw new \RuntimeException(sprintf('The Redis item key "%s" is too long (%s bytes). Allowed maximum size is 2^31 bytes.', $name, $length));
        }

        return true;
    }

    /**
     * Retrieves an item from the Redis server.
     *
     * @param string $key
     * @param int    $serializer
     *
     * @return mixed
     */
    protected function getValue($key, $serializer = self::REDIS_SERIALIZER_NONE)
    {
        $value = $this->redis->get($key);

        if ($value && (self::REDIS_SERIALIZER_PHP === $serializer)) {
            $value = unserialize($value);
        }

        return $value;
    }

    /**
     * Stores an item on the Redis server under the specified key.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expiration
     * @param int    $serializer
     *
     * @return bool
     */
    protected function setValue($key, $value, $expiration = 0, $serializer = self::REDIS_SERIALIZER_NONE)
    {
        if (self::REDIS_SERIALIZER_PHP === $serializer) {
            $value = serialize($value);
        }

        return $this->redis->setex($key, $expiration, $value);
    }

    /**
     * Appends data to an existing item on the Redis server.
     *
     * @param  string $key
     * @param  string $value
     * @param  int    $expiration
     * @return bool
     */
    protected function appendValue($key, $value, $expiration = 0)
    {
        if ($this->redis->exists($key)) {
            $this->redis->append($key, $value);

            return $this->redis->expire($key, $expiration);
        }

        return $this->redis->setex($key, $expiration, $value);
    }

    /**
     * Removes the specified keys.
     *
     * @param  array $keys
     * @return bool
     */
    protected function delete(array $keys)
    {
        return (bool) $this->redis->del($keys);
    }

    /**
     * Prefixes the key.
     *
     * @param  string $key
     * @return string
     */
    protected function prefixKey($key)
    {
        return self::TOKEN_PREFIX . $key;
    }
}
