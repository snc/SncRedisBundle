<?php

namespace Snc\RedisBundle\Client\Phpredis;

use RedisCluster;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @internal
 *
 * @author Igor Scabini <furester@gmail.com>
 */
class ClientCluster extends RedisCluster
{
    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var Stopwatch|null;
     */
    protected $stopwatch;

    /**
     * @param array            $parameters  List of parameters (only `alias` key is handled)
     * @param RedisLogger|null $logger      A RedisLogger instance
     * @param array            $seeds       https://github.com/phpredis/phpredis/blob/develop/cluster.markdown#readme
     * @param float|null       $timeout     https://github.com/phpredis/phpredis/blob/develop/cluster.markdown#readme
     * @param float|null       $readTimeout https://github.com/phpredis/phpredis/blob/develop/cluster.markdown#readme
     * @param bool|null        $persistent  https://github.com/phpredis/phpredis/blob/develop/cluster.markdown#readme
     * @param string|null      $password    https://github.com/phpredis/phpredis/blob/develop/cluster.markdown#readme
     *
     * @throws \RedisClusterException
     */
    public function __construct(
        array $parameters,
        ?RedisLogger $logger,
        array $seeds,
        ?float $timeout,
        ?float $readTimeout,
        ?bool $persistent,
        string $password = null,
        ?Stopwatch $stopwatch = null
    ) {
        $this->logger = $logger;
        $this->alias = $parameters['alias'] ?? '';

        if (version_compare(phpversion('redis'), '4.3.0', '>=')) {
            parent::__construct(null, $seeds, $timeout, $readTimeout, $persistent ?? false, $password);
        } else {
            parent::__construct(null, $seeds, $timeout, $readTimeout, $persistent ?? false);
        }
        $this->stopwatch = $stopwatch;
    }

    /**
     * Proxy function.
     *
     * @param string $name      A command name
     * @param array  $arguments Lit of command arguments
     *
     * @throws \RuntimeException If no Redis instance is defined
     *
     * @return mixed
     */
    private function call($name, array $arguments = array())
    {
        $commandName = $this->getCommandString($name, $arguments);

        if ($this->stopwatch) {
            $event = $this->stopwatch->start($commandName, 'redis');
        }

        $startTime = microtime(true);
        $result = call_user_func_array("parent::$name", $arguments);

        if (isset($event)) {
            $event->stop();
        }

        if (null !== $this->logger) {
            $this->logger->logCommand($commandName, (microtime(true) - $startTime) * 1000, $this->alias, false);
        }

        return $result;
    }

    /**
     * Returns a string representation of the given command including arguments.
     *
     * @param string $command   A command name
     * @param array  $arguments List of command arguments
     *
     * @return string
     */
    private function getCommandString($command, array $arguments)
    {
        $list = array();
        $this->flatten($arguments, $list);

        return trim(strtoupper($command).' '.implode(' ', $list));
    }

    /**
     * Flatten arguments to single dimension array.
     *
     * @param array $arguments An array of command arguments
     * @param array $list      Holder of results
     */
    private function flatten($arguments, array &$list)
    {
        foreach ($arguments as $key => $item) {
            if (!is_numeric($key)) {
                $list[] = $key;
            }

            if (is_scalar($item)) {
                $list[] = strval($item);
            } elseif (null === $item) {
                $list[] = '<null>';
            } else {
                $this->flatten($item, $list);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function ping($key_or_address)
    {
        return $this->call('ping', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->call('get', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $timeout = null)
    {
        return $this->call('set', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setex($key, $ttl, $value)
    {
        return $this->call('setex', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function psetex($key, $ttl, $value)
    {
        return $this->call('psetex', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setnx($key, $value)
    {
        return $this->call('setnx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function del($key, ...$other_keys)
    {
        return $this->call('del', array_merge([$key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function multi($mode = RedisCluster::MULTI)
    {
        return $this->call('multi', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function exec()
    {
        return $this->call('exec', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function discard()
    {
        return $this->call('discard', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function watch($key, ...$other_keys)
    {
        return $this->call('watch', array_merge([$key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function unwatch()
    {
        return $this->call('unwatch', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe($channels, $callback)
    {
        return $this->call('subscribe', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function psubscribe($patterns, $callback)
    {
        return $this->call('psubscribe', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function publish($channel, $message)
    {
        return $this->call('publish', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pubsub($key_or_address, $arg = null, ...$other_args)
    {
        return $this->call('pubsub', array_merge([$key_or_address, $arg], $other_args));
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribe($channel, ...$other_channels)
    {
        return $this->call('unsubscribe', array_merge([$channel], $other_channels));
    }

    /**
     * {@inheritdoc}
     */
    public function punsubscribe($pattern, ...$other_patterns)
    {
        return $this->call('punsubscribe', array_merge([$pattern], $other_patterns));
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key, ...$other_keys)
    {
        return $this->call('exists', array_merge([$key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function incr($key)
    {
        return $this->call('incr', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function incrByFloat($key, $increment)
    {
        return $this->call('incrByFloat', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function incrBy($key, $value)
    {
        return $this->call('incrBy', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function decr($key)
    {
        return $this->call('decr', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function decrBy($key, $value)
    {
        return $this->call('decrBy', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys)
    {
        return $this->call('getMultiple', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lPush($key, $value1, $value2 = null, $valueN = null)
    {
        return $this->call('lPush', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rPush($key, $value1, $value2 = null, $valueN = null)
    {
        return $this->call('rPush', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lPushx($key, $value)
    {
        return $this->call('lPushx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rPushx($key, $value)
    {
        return $this->call('rPushx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lPop($key)
    {
        return $this->call('lPop', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rPop($key)
    {
        return $this->call('rPop', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function blPop($key, $timeout_or_key, ...$extra_args)
    {
        return $this->call('blPop', array_merge([$key, $timeout_or_key], $extra_args));
    }

    /**
     * {@inheritdoc}
     */
    public function brPop($key, $timeout_or_key, ...$extra_args)
    {
        return $this->call('brPop', array_merge([$key, $timeout_or_key], $extra_args));
    }

    /**
     * {@inheritdoc}
     */
    public function lLen($key)
    {
        return $this->call('lLen', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lSize($key)
    {
        return $this->call('lSize', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lIndex($key, $index)
    {
        return $this->call('lIndex', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lGet($key, $index)
    {
        return $this->call('lGet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lSet($key, $index, $value)
    {
        return $this->call('lSet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lRange($key, $start, $end)
    {
        return $this->call('lRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lGetRange($key, $start, $end)
    {
        return $this->call('lGetRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lTrim($key, $start, $stop)
    {
        return $this->call('lTrim', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lRem($key, $value)
    {
        return $this->call('lRem', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lInsert($key, $position, $pivot, $value)
    {
        return $this->call('lInsert', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sAdd($key, $value1, $value2 = null, $valueN = null)
    {
        return $this->call('sAdd', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sRem($key, $member, ...$other_members)
    {
        return $this->call('sRem', array_merge([$key, $member], $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function sMove($srcKey, $dstKey, $member)
    {
        return $this->call('sMove', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sIsMember($key, $value)
    {
        return $this->call('sIsMember', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sCard($key)
    {
        return $this->call('sCard', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sPop($key)
    {
        return $this->call('sPop', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sRandMember($key, $count = null)
    {
        return $this->call('sRandMember', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sInter($key, ...$other_keys)
    {
        return $this->call('sInter', array_merge([$key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sInterStore($dst, $key, ...$other_keys)
    {
        return $this->call('sInterStore', array_merge([$dst, $key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sUnion($key, ...$other_keys)
    {
        return $this->call('sUnion', array_merge([$key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sUnionStore($dst, $key, ...$other_keys)
    {
        return $this->call('sUnionStore', array_merge([$dst, $key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sDiff($key, ...$other_keys)
    {
        return $this->call('sDiff', array_merge([$key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sDiffStore($dst, $key, ...$other_keys)
    {
        return $this->call('sDiffStore', array_merge([$dst, $key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sMembers($key)
    {
        return $this->call('sMembers', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sScan($key, &$iterator, $pattern = null, $count = null)
    {
        return $this->call('sScan', array($key, &$iterator, $pattern, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function getSet($key, $value)
    {
        return $this->call('getSet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function randomKey($key_or_address)
    {
        return $this->call('randomKey', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rename($srcKey, $dstKey)
    {
        return $this->call('rename', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function renameKey($srcKey, $dstKey)
    {
        return $this->call('renameKey', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function renameNx($srcKey, $dstKey)
    {
        return $this->call('renameNx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function expire($key, $ttl)
    {
        return $this->call('expire', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pExpire($key, $ttl)
    {
        return $this->call('pExpire', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeout($key, $ttl)
    {
        return $this->call('setTimeout', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function expireAt($key, $timestamp)
    {
        return $this->call('expireAt', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pExpireAt($key, $timestamp)
    {
        return $this->call('pExpireAt', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function keys($pattern)
    {
        return $this->call('keys', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys($pattern)
    {
        return $this->call('getKeys', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function dbSize($key_or_address)
    {
        return $this->call('dbSize', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bgrewriteaof($key_or_address)
    {
        return $this->call('bgrewriteaof', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function slaveof($host = '127.0.0.1', $port = 6379)
    {
        return $this->call('slaveof', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function object($string = '', $key = '')
    {
        return $this->call('object', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function save($key_or_address)
    {
        return $this->call('save', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bgsave($key_or_address)
    {
        return $this->call('bgsave', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lastSave($key_or_address)
    {
        return $this->call('lastSave', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function wait($numSlaves, $timeout)
    {
        return $this->call('wait', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function type($key)
    {
        return $this->call('type', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function append($key, $value)
    {
        return $this->call('append', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getRange($key, $start, $end)
    {
        return $this->call('getRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function substr($key, $start, $end)
    {
        return $this->call('substr', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setRange($key, $offset, $value)
    {
        return $this->call('setRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function strlen($key)
    {
        return $this->call('strlen', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bitpos($key, $bit, $start = 0, $end = null)
    {
        return $this->call('bitpos', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBit($key, $offset)
    {
        return $this->call('getBit', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setBit($key, $offset, $value)
    {
        return $this->call('setBit', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bitCount($key)
    {
        return $this->call('bitCount', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bitOp($operation, $ret_key, $key, ...$other_keys)
    {
        return $this->call('bitOp', array_merge([$operation, $ret_key, $key], $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function flushDB($key_or_address, $async = null)
    {
        return $this->call('flushDB', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll($key_or_address, $async = NULL)
    {
        return $this->call('flushAll', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sort($key, $option = null)
    {
        return $this->call('sort', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function info($key_or_address, $option = null)
    {
        return $this->call('info', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function ttl($key)
    {
        return $this->call('ttl', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pttl($key)
    {
        return $this->call('pttl', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function persist($key)
    {
        return $this->call('persist', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function mset(array $array)
    {
        return $this->call('mset', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function mget(array $array)
    {
        return $this->call('mget', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function msetnx(array $array)
    {
        return $this->call('msetnx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rpoplpush($srcKey, $dstKey)
    {
        return $this->call('rpoplpush', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function brpoplpush($srcKey, $dstKey, $timeout)
    {
        return $this->call('brpoplpush', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zAdd($key, $score, $value, ...$extra_args)
    {
        return $this->call('zAdd', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRange($key, $start, $end, $withscores = null)
    {
        return $this->call('zRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRem($key, $member, ...$other_members)
    {
        return $this->call('zRem', array_merge([$key, $member], $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function zDelete($key, $member, ...$other_members)
    {
        return $this->call('zDelete', array_merge([$key, $member], $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRange($key, $start, $end, $withscore = null)
    {
        return $this->call('zRevRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByScore($key, $start, $end, array $options = array())
    {
        return $this->call('zRangeByScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByScore($key, $start, $end, array $options = array())
    {
        return $this->call('zRevRangeByScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByLex($key, $min, $max, $offset = null, $limit = null)
    {
        return $this->call('zRangeByLex', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByLex($key, $min, $max, $offset = null, $limit = null)
    {
        return $this->call('zRevRangeByLex', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zCount($key, $start, $end)
    {
        return $this->call('zCount', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByScore($key, $start, $end)
    {
        return $this->call('zRemRangeByScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByScore($key, $start, $end)
    {
        return $this->call('zDeleteRangeByScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByRank($key, $start, $end)
    {
        return $this->call('zRemRangeByRank', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByRank($key, $start, $end)
    {
        return $this->call('zDeleteRangeByRank', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zCard($key)
    {
        return $this->call('zCard', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zSize($key)
    {
        return $this->call('zSize', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zScore($key, $member)
    {
        return $this->call('zScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRank($key, $member)
    {
        return $this->call('zRank', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRank($key, $member)
    {
        return $this->call('zRevRank', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zIncrBy($key, $value, $member)
    {
        return $this->call('zIncrBy', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zScan($key, &$iterator, $pattern = null, $count = null)
    {
        return $this->call('zScan', array($key, &$iterator, $pattern, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function hSet($key, $hashKey, $value)
    {
        return $this->call('hSet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hSetNx($key, $hashKey, $value)
    {
        return $this->call('hSetNx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hGet($key, $hashKey)
    {
        return $this->call('hGet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hLen($key)
    {
        return $this->call('hLen', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hDel($key, $member, ...$other_members)
    {
        return $this->call('hDel', array_merge([$key, $member], $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function hKeys($key)
    {
        return $this->call('hKeys', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hVals($key)
    {
        return $this->call('hVals', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hGetAll($key)
    {
        return $this->call('hGetAll', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hExists($key, $hashKey)
    {
        return $this->call('hExists', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrBy($key, $hashKey, $value)
    {
        return $this->call('hIncrBy', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrByFloat($key, $field, $increment)
    {
        return $this->call('hIncrByFloat', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hMSet($key, $hashKeys)
    {
        return $this->call('hMset', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hMGet($key, $hashKeys)
    {
        return $this->call('hMGet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hScan($key, &$iterator, $pattern = null, $count = null)
    {
        return $this->call('hScan', array($key, &$iterator, $pattern, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function config($key_or_address, $arg = null, ...$other_args)
    {
        return $this->call('config', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate($script, $args = array(), $numKeys = 0)
    {
        return $this->call('evaluate', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function evalSha($scriptSha, $args = array(), $numKeys = 0)
    {
        return $this->call('evalSha', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateSha($scriptSha, $args = array(), $numKeys = 0)
    {
        return $this->call('evaluateSha', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function script($key_or_address, $arg = null, ...$other_args)
    {
        return $this->call('script', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getLastError()
    {
        return $this->call('getLastError', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function clearLastError()
    {
        return $this->call('clearLastError', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function _prefix($value)
    {
        return $this->call('_prefix', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function _unserialize($value)
    {
        return $this->call('_unserialize', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function _serialize($value)
    {
        return $this->call('_serialize', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function dump($key)
    {
        return $this->call('dump', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function restore($key, $ttl, $value)
    {
        return $this->call('restore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function migrate($host, $port, $key, $db, $timeout, $copy = false, $replace = false)
    {
        return $this->call('migrate', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function time()
    {
        return $this->call('time', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function scan(&$i_iterator, $str_node, $str_pattern = null, $i_count = null)
    {
        return $this->call('scan', array(&$iterator, $pattern, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function pfAdd($key, array $elements)
    {
        return $this->call('pfAdd', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pfCount($key)
    {
        return $this->call('pfCount', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pfMerge($destkey, array $sourcekeys)
    {
        return $this->call('pfMerge', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rawCommand($cmd, ...$args)
    {
        return $this->call('rawCommand', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getMode()
    {
        return $this->call('getMode', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xDel($str_key, $arr_ids)
    {
        return $this->call('xDel', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xGroup($str_operation, $str_key = null, $str_arg1 = null, $str_arg2 = null, $str_arg3 = null)
    {
        return $this->call('xGroup', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xInfo($str_cmd, $str_key = null, $str_group = null)
    {
        return $this->call('xInfo', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xLen($str_stream)
    {
        return $this->call('xLen', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xPending($str_stream, $str_group, $str_start = null, $str_end = null, $i_count = null, $str_consumer = null)
    {
        return $this->call('xPending', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xRange($str_stream, $str_start, $str_end, $i_count = null)
    {
        return $this->call('xRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xRead($arr_streams, $i_count = null, $i_block = null)
    {
        return $this->call('xRead', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xReadGroup($str_group, $str_consumer, array $arr_streams, $i_count = null, $i_block = null)
    {
        return $this->call('xReadGroup', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xRevRange($str_stream, $str_end, $str_start, $i_count = null)
    {
        return $this->call('xRevRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function xTrim($str_key, $i_maxlen, $boo_approximate = null)
    {
        return $this->call('xTrim', func_get_args());
    }
}
