<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 * (c) Yassine Khial <yassine.khial@blablacar.com>
 * (c) Pierre Boudelle <pierre.boudelle@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Phpredis;

use Redis;
use Snc\RedisBundle\Logger\RedisLogger;

/**
 * PHP Redis client with logger.
 *
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 * @author Yassine Khial <yassine.khial@blablacar.com>
 * @author Pierre Boudelle <pierre.boudelle@gmail.com>
 */
class Client extends Redis
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
     * Constructor.
     *
     * @param array       $parameters List of parameters (only `alias` key is handled)
     * @param RedisLogger $logger     A RedisLogger instance
     */
    public function __construct(array $parameters = array(), RedisLogger $logger = null)
    {
        $this->logger = $logger;
        $this->alias = isset($parameters['alias']) ? $parameters['alias'] : '';
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
        $startTime = microtime(true);
        $result = call_user_func_array("parent::$name", $arguments);
        $duration = (microtime(true) - $startTime) * 1000;

        $this->logger->logCommand($this->getCommandString($name, $arguments), $duration, $this->alias, false);

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
    public function ping(...$args)
    {
        return $this->call('ping', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function get(...$args)
    {
        return $this->call('get', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function set(...$args)
    {
        return $this->call('set', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function setEx(...$args)
    {
        return $this->call('setEx', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function setNx(...$args)
    {
        return $this->call('setNx', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function del(...$args)
    {
        return $this->call('del', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(...$args)
    {
        return $this->call('delete', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function multi(...$args)
    {
        return $this->call('multi', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(...$args)
    {
        return $this->call('exec', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function discard(...$args)
    {
        return $this->call('discard', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function watch(...$args)
    {
        return $this->call('watch', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function unwatch(...$args)
    {
        return $this->call('unwatch', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(...$args)
    {
        return $this->call('subscribe', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function pSubscribe(...$args)
    {
        return $this->call('psubscribe', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function publish(...$args)
    {
        return $this->call('publish', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function pubsub(...$args)
    {
        return $this->call('pubsub', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(...$args)
    {
        return $this->call('exists', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function incr(...$args)
    {
        return $this->call('incr', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function incrByFloat(...$args)
    {
        return $this->call('incrByFloat', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function incrBy(...$args)
    {
        return $this->call('incrBy', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function decr(...$args)
    {
        return $this->call('decr', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function decrBy(...$args)
    {
        return $this->call('decrBy', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(...$args)
    {
        return $this->call('getMultiple', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lPush(...$args)
    {
        return $this->call('lPush', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function rPush(...$args)
    {
        return $this->call('rPush', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lPushx(...$args)
    {
        return $this->call('lPushx', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function rPushx(...$args)
    {
        return $this->call('rPushx', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lPop(...$args)
    {
        return $this->call('lPop', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function rPop(...$args)
    {
        return $this->call('rPop', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function blPop(...$args)
    {
        return $this->call('blPop', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function brPop(...$args)
    {
        return $this->call('brPop', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lLen(...$args)
    {
        return $this->call('lLen', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lSize(...$args)
    {
        return $this->call('lSize', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lIndex(...$args)
    {
        return $this->call('lIndex', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lGet(...$args)
    {
        return $this->call('lGet', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lSet(...$args)
    {
        return $this->call('lSet', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lRange(...$args)
    {
        return $this->call('lRange', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lGetRange(...$args)
    {
        return $this->call('lGetRange', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lTrim(...$args)
    {
        return $this->call('lTrim', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function listTrim(...$args)
    {
        return $this->call('listTrim', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lRem(...$args)
    {
        return $this->call('lRem', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lRemove(...$args)
    {
        return $this->call('lRemove', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lInsert(...$args)
    {
        return $this->call('lInsert', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sAdd(...$args)
    {
        return $this->call('sAdd', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sRem(...$args)
    {
        return $this->call('sRem', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sRemove(...$args)
    {
        return $this->call('sRemove', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sMove(...$args)
    {
        return $this->call('sMove', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sIsMember(...$args)
    {
        return $this->call('sIsMember', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sContains(...$args)
    {
        return $this->call('sContains', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sCard(...$args)
    {
        return $this->call('sCard', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sPop(...$args)
    {
        return $this->call('sPop', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sRandMember(...$args)
    {
        return $this->call('sRandMember', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sInter(...$args)
    {
        return $this->call('sInter', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sInterStore(...$args)
    {
        return $this->call('sInterStore', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sUnion(...$args)
    {
        return $this->call('sUnion', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sUnionStore(...$args)
    {
        return $this->call('sUnionStore', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sDiff(...$args)
    {
        return $this->call('sDiff', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sDiffStore(...$args)
    {
        return $this->call('sDiffStore', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sMembers(...$args)
    {
        return $this->call('sMembers', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sGetMembers(...$args)
    {
        return $this->call('sGetMembers', ...$args);
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
    public function getSet(...$args)
    {
        return $this->call('getSet', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function randomKey(...$args)
    {
        return $this->call('randomKey', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function move(...$args)
    {
        return $this->call('move', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(...$args)
    {
        return $this->call('rename', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function renameKey(...$args)
    {
        return $this->call('renameKey', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function renameNx(...$args)
    {
        return $this->call('renameNx', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function expire(...$args)
    {
        return $this->call('expire', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function pExpire(...$args)
    {
        return $this->call('pExpire', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeout(...$args)
    {
        return $this->call('setTimeout', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function expireAt(...$args)
    {
        return $this->call('expireAt', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function pExpireAt(...$args)
    {
        return $this->call('pExpireAt', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(...$args)
    {
        return $this->call('keys', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys(...$args)
    {
        return $this->call('getKeys', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function dbSize(...$args)
    {
        return $this->call('dbSize', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function bgrewriteaof(...$args)
    {
        return $this->call('bgrewriteaof', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function slaveof(...$args)
    {
        return $this->call('slaveof', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function object(...$args)
    {
        return $this->call('object', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function save(...$args)
    {
        return $this->call('save', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function bgsave(...$args)
    {
        return $this->call('bgsave', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function lastSave(...$args)
    {
        return $this->call('lastSave', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function wait(...$args)
    {
        return $this->call('wait', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function type(...$args)
    {
        return $this->call('type', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function append(...$args)
    {
        return $this->call('append', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function getRange(...$args)
    {
        return $this->call('getRange', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function substr(...$args)
    {
        return $this->call('substr', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function setRange(...$args)
    {
        return $this->call('setRange', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function strlen(...$args)
    {
        return $this->call('strlen', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function bitpos(...$args)
    {
        return $this->call('bitpos', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function getBit(...$args)
    {
        return $this->call('getBit', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function setBit(...$args)
    {
        return $this->call('setBit', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function bitCount(...$args)
    {
        return $this->call('bitCount', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function bitOp(...$args)
    {
        return $this->call('bitOp', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function flushDB(...$args)
    {
        return $this->call('flushDB', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll(...$args)
    {
        return $this->call('flushAll', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function sort(...$args)
    {
        return $this->call('sort', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function info(...$args)
    {
        return $this->call('info', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function resetStat(...$args)
    {
        return $this->call('resetStat', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function ttl(...$args)
    {
        return $this->call('ttl', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function pttl(...$args)
    {
        return $this->call('pttl', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(...$args)
    {
        return $this->call('persist', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function mset(...$args)
    {
        return $this->call('mset', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function mget(...$args)
    {
        return $this->call('mget', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function msetnx(...$args)
    {
        return $this->call('msetnx', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function rpoplpush(...$args)
    {
        return $this->call('rpoplpush', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function brpoplpush(...$args)
    {
        return $this->call('brpoplpush', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zAdd(...$args)
    {
        return $this->call('zAdd', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRange(...$args)
    {
        return $this->call('zRange', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRem(...$args)
    {
        return $this->call('zRem', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zDelete(...$args)
    {
        return $this->call('zDelete', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRange(...$args)
    {
        return $this->call('zRevRange', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByScore(...$args)
    {
        return $this->call('zRangeByScore', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByScore(...$args)
    {
        return $this->call('zRevRangeByScore', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByLex(...$args)
    {
        return $this->call('zRangeByLex', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByLex(...$args)
    {
        return $this->call('zRevRangeByLex', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zCount(...$args)
    {
        return $this->call('zCount', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByScore(...$args)
    {
        return $this->call('zRemRangeByScore', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByScore(...$args)
    {
        return $this->call('zDeleteRangeByScore', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByRank(...$args)
    {
        return $this->call('zRemRangeByRank', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByRank(...$args)
    {
        return $this->call('zDeleteRangeByRank', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zCard(...$args)
    {
        return $this->call('zCard', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zSize(...$args)
    {
        return $this->call('zSize', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zScore(...$args)
    {
        return $this->call('zScore', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRank(...$args)
    {
        return $this->call('zRank', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRank(...$args)
    {
        return $this->call('zRevRank', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zIncrBy(...$args)
    {
        return $this->call('zIncrBy', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zUnion(...$args)
    {
        return $this->call('zUnion', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function zInter(...$args)
    {
        return $this->call('zInter', ...$args);
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
    public function hSet(...$args)
    {
        return $this->call('hSet', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hSetNx(...$args)
    {
        return $this->call('hSetNx', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hGet(...$args)
    {
        return $this->call('hGet', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hLen(...$args)
    {
        return $this->call('hLen', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hDel(...$args)
    {
        return $this->call('hDel', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hKeys(...$args)
    {
        return $this->call('hKeys', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hVals(...$args)
    {
        return $this->call('hVals', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hGetAll(...$args)
    {
        return $this->call('hGetAll', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hExists(...$args)
    {
        return $this->call('hExists', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrBy(...$args)
    {
        return $this->call('hIncrBy', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrByFloat(...$args)
    {
        return $this->call('hIncrByFloat', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hMset(...$args)
    {
        return $this->call('hMset', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function hMGet(...$args)
    {
        return $this->call('hMGet', ...$args);
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
    public function config(...$args)
    {
        return $this->call('config', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(...$args)
    {
        return $this->call('evaluate', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function evalSha(...$args)
    {
        return $this->call('evalSha', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateSha(...$args)
    {
        return $this->call('evaluateSha', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function script(...$args)
    {
        return $this->call('script', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastError(...$args)
    {
        return $this->call('getLastError', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function clearLastError(...$args)
    {
        return $this->call('clearLastError', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function dump(...$args)
    {
        return $this->call('dump', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function restore(...$args)
    {
        return $this->call('restore', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function migrate(...$args)
    {
        return $this->call('migrate', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function time(...$args)
    {
        return $this->call('time', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function scan(&$iterator, $pattern = null, $count = null)
    {
        return $this->call('scan', array(&$iterator, $pattern, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function pfAdd(...$args)
    {
        return $this->call('pfAdd', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function pfCount(...$args)
    {
        return $this->call('pfCount', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function pfMerge(...$args)
    {
        return $this->call('pfMerge', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function rawCommand(...$args)
    {
        return $this->call('rawCommand', ...$args);
    }

    /**
     * {@inheritdoc}
     */
    public function getMode(...$args)
    {
        return $this->call('getMode', ...$args);
    }
}
