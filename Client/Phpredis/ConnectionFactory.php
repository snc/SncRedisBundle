<?php
namespace Snc\RedisBundle\Client\Phpredis;

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
