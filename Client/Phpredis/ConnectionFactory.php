<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Phpredis;

/**
 * ConnectionFactory for PHPRedis
 */
class ConnectionFactory
{
    private static $connectionsMap = array();

    public static function get(array $args)
    {
        $key = md5(serialize($args));
        if (!array_key_exists($key, self::$connectionsMap)) {
            self::$connectionsMap[$key] = self::makeRedis($args);
        }

        return self::$connectionsMap[$key];
    }

    private static function makeRedis(array $args)
    {
        $redis = new \Redis;
        call_user_func_array(array($redis, $args['connectMethod']), $args['connectParams']);
        if (isset($args['auth'])) {
            $redis->auth($args['auth']);
        }
        if (isset($args['select'])) {
            $redis->select($args['select']);
        }

        return $redis;
    }
}
