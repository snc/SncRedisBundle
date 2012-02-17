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
use Snc\RedisBundle\Doctrine\Cache\RedisSetCache;

class RedisSetCacheTest extends RedisCacheTest
{
    protected function _getCacheDriver()
    {
        $driver = new RedisSetCache();
        $driver->setRedis($this->_redis);
        return $driver;
    }
}
