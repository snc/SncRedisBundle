<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Tests\Common\Cache\CacheTest;
use Snc\RedisBundle\Doctrine\Cache\RedisCache;

class RedisCacheTest extends CacheTest
{
    private $_redis;

    public function setUp()
    {
        if (class_exists('\Predis\Client')) {
            $config = 'tcp://127.0.0.1:6379';
            $this->_redis = new \Predis\Client($config);
            try {
                $ok = $this->_redis->ping();
            } catch (\Exception $e) {
                $ok = false;
            }
            if (!$ok) {
                $this->markTestSkipped(sprintf('The %s requires a redis instance listening on %s.', __CLASS__, $config));
            }
        } else {
            $this->markTestSkipped(sprintf('The %s requires the predis library.', __CLASS__));
        }
    }

    protected function _getCacheDriver()
    {
        $driver = new RedisCache();
        $driver->setRedis($this->_redis);
        return $driver;
    }
}
