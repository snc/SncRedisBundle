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
 * Simple \Redis Proxy:
 * This object calls directly \Redis functions and close the connection when this object is destructed
 */
class BaseClient implements ClientInterface
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * {@inheritDoc}
     */
    public function setRedis(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Proxy function
     *
     * @param string $name      A command name
     * @param array  $arguments Lit of command arguments
     *
     * @throws \RuntimeException If no Redis instance is defined
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (null === $this->redis) {
            throw new \RuntimeException('You have to define a Redis instance before calling any command.');
        }

        return call_user_func_array(array($this->redis, $name), $arguments);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (null !== $this->redis) {
            $this->redis->close();
        }
    }
}
