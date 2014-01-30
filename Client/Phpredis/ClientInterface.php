<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 * (c) Yassine Khial <yassine.khial@blablacar.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Phpredis;

use Redis;

/**
 * \Redis object Proxy interface. When we implement this interface, we have to:
 * - either define all Redis class functions (connect, exists, get...) (and so overload that functions if needed)
 * - either define the __call magic method and call directly redis functions
 * - or both (mixed)
 */
interface ClientInterface
{
    /**
     * Define the Redis instance to wrap
     *
     * @param \Redis $redis
     */
    public function setRedis(Redis $redis);
}
