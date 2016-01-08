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
    public function ping()
    {
        return $this->call('ping', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->call('get', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function set()
    {
        return $this->call('set', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setEx()
    {
        return $this->call('setEx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setNx()
    {
        return $this->call('setNx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function del()
    {
        return $this->call('del', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        return $this->call('delete', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function multi()
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
    public function watch()
    {
        return $this->call('watch', func_get_args());
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
    public function subscribe()
    {
        return $this->call('subscribe', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pSubscribe()
    {
        return $this->call('psubscribe', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function publish()
    {
        return $this->call('publish', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pubsub()
    {
        return $this->call('pubsub', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function exists()
    {
        return $this->call('exists', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function incr()
    {
        return $this->call('incr', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function incrByFloat()
    {
        return $this->call('incrByFloat', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function incrBy()
    {
        return $this->call('incrBy', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function decr()
    {
        return $this->call('decr', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function decrBy()
    {
        return $this->call('decrBy', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple()
    {
        return $this->call('getMultiple', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lPush()
    {
        return $this->call('lPush', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rPush()
    {
        return $this->call('rPush', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lPushx()
    {
        return $this->call('lPushx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rPushx()
    {
        return $this->call('rPushx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lPop()
    {
        return $this->call('lPop', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rPop()
    {
        return $this->call('rPop', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function blPop()
    {
        return $this->call('blPop', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function brPop()
    {
        return $this->call('brPop', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lLen()
    {
        return $this->call('lLen', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lSize()
    {
        return $this->call('lSize', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lIndex()
    {
        return $this->call('lIndex', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lGet()
    {
        return $this->call('lGet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lSet()
    {
        return $this->call('lSet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lRange()
    {
        return $this->call('lRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lGetRange()
    {
        return $this->call('lGetRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lTrim()
    {
        return $this->call('lTrim', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function listTrim()
    {
        return $this->call('listTrim', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lRem()
    {
        return $this->call('lRem', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lRemove()
    {
        return $this->call('lRemove', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lInsert()
    {
        return $this->call('lInsert', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sAdd()
    {
        return $this->call('sAdd', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sRem()
    {
        return $this->call('sRem', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sRemove()
    {
        return $this->call('sRemove', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sMove()
    {
        return $this->call('sMove', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sIsMember()
    {
        return $this->call('sIsMember', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sContains()
    {
        return $this->call('sContains', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sCard()
    {
        return $this->call('sCard', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sPop()
    {
        return $this->call('sPop', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sRandMember()
    {
        return $this->call('sRandMember', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sInter()
    {
        return $this->call('sInter', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sInterStore()
    {
        return $this->call('sInterStore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sUnion()
    {
        return $this->call('sUnion', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sUnionStore()
    {
        return $this->call('sUnionStore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sDiff()
    {
        return $this->call('sDiff', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sDiffStore()
    {
        return $this->call('sDiffStore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sMembers()
    {
        return $this->call('sMembers', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sGetMembers()
    {
        return $this->call('sGetMembers', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sScan($key, &$iterator, $pattern = null, $count = null)
    {
        return $this->call('sScan', array($key, $iterator, $pattern, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function getSet()
    {
        return $this->call('getSet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function randomKey()
    {
        return $this->call('randomKey', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function move()
    {
        return $this->call('move', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rename()
    {
        return $this->call('rename', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function renameKey()
    {
        return $this->call('renameKey', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function renameNx()
    {
        return $this->call('renameNx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function expire()
    {
        return $this->call('expire', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pExpire()
    {
        return $this->call('pExpire', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeout()
    {
        return $this->call('setTimeout', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function expireAt()
    {
        return $this->call('expireAt', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pExpireAt()
    {
        return $this->call('pExpireAt', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return $this->call('keys', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        return $this->call('getKeys', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function dbSize()
    {
        return $this->call('dbSize', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bgrewriteaof()
    {
        return $this->call('bgrewriteaof', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function slaveof()
    {
        return $this->call('slaveof', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function object()
    {
        return $this->call('object', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        return $this->call('save', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bgsave()
    {
        return $this->call('bgsave', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lastSave()
    {
        return $this->call('lastSave', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function wait()
    {
        return $this->call('wait', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function type()
    {
        return $this->call('type', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function append()
    {
        return $this->call('append', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getRange()
    {
        return $this->call('getRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function substr()
    {
        return $this->call('substr', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setRange()
    {
        return $this->call('setRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function strlen()
    {
        return $this->call('strlen', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bitpos()
    {
        return $this->call('bitpos', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBit()
    {
        return $this->call('getBit', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setBit()
    {
        return $this->call('setBit', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bitCount()
    {
        return $this->call('bitCount', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function bitOp()
    {
        return $this->call('bitOp', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function flushDB()
    {
        return $this->call('flushDB', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->call('flushAll', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function sort()
    {
        return $this->call('sort', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function info()
    {
        return $this->call('info', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function resetStat()
    {
        return $this->call('resetStat', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function ttl()
    {
        return $this->call('ttl', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pttl()
    {
        return $this->call('pttl', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function persist()
    {
        return $this->call('persist', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function mset()
    {
        return $this->call('mset', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function mget()
    {
        return $this->call('mget', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function msetnx()
    {
        return $this->call('msetnx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rpoplpush()
    {
        return $this->call('rpoplpush', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function brpoplpush()
    {
        return $this->call('brpoplpush', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zAdd()
    {
        return $this->call('zAdd', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRange()
    {
        return $this->call('zRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRem()
    {
        return $this->call('zRem', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zDelete()
    {
        return $this->call('zDelete', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRange()
    {
        return $this->call('zRevRange', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByScore()
    {
        return $this->call('zRangeByScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByScore()
    {
        return $this->call('zRevRangeByScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByLex()
    {
        return $this->call('zRangeByLex', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByLex()
    {
        return $this->call('zRevRangeByLex', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zCount()
    {
        return $this->call('zCount', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByScore()
    {
        return $this->call('zRemRangeByScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByScore()
    {
        return $this->call('zDeleteRangeByScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByRank()
    {
        return $this->call('zRemRangeByRank', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByRank()
    {
        return $this->call('zDeleteRangeByRank', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zCard()
    {
        return $this->call('zCard', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zSize()
    {
        return $this->call('zSize', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zScore()
    {
        return $this->call('zScore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRank()
    {
        return $this->call('zRank', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRank()
    {
        return $this->call('zRevRank', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zIncrBy()
    {
        return $this->call('zIncrBy', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zUnion()
    {
        return $this->call('zUnion', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zInter()
    {
        return $this->call('zInter', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function zScan($key, &$iterator, $pattern = null, $count = null)
    {
        return $this->call('zScan', array($key, $iterator, $pattern, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function hSet()
    {
        return $this->call('hSet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hSetNx()
    {
        return $this->call('hSetNx', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hGet()
    {
        return $this->call('hGet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hLen()
    {
        return $this->call('hLen', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hDel()
    {
        return $this->call('hDel', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hKeys()
    {
        return $this->call('hKeys', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hVals()
    {
        return $this->call('hVals', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hGetAll()
    {
        return $this->call('hGetAll', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hExists()
    {
        return $this->call('hExists', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrBy()
    {
        return $this->call('hIncrBy', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrByFloat()
    {
        return $this->call('hIncrByFloat', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hMset()
    {
        return $this->call('hMset', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hMGet()
    {
        return $this->call('hMGet', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function hScan($key, &$iterator, $pattern = null, $count = null)
    {
        return $this->call('hScan', array($key, $iterator, $pattern, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function config()
    {
        return $this->call('config', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate()
    {
        return $this->call('evaluate', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function evalSha()
    {
        return $this->call('evalSha', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateSha()
    {
        return $this->call('evaluateSha', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function script()
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
    public function dump()
    {
        return $this->call('dump', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function restore()
    {
        return $this->call('restore', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function migrate()
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
    public function scan(&$iterator, $pattern = null, $count = null)
    {
        return $this->call('scan', array($iterator, $pattern, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function pfAdd()
    {
        return $this->call('pfAdd', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pfCount()
    {
        return $this->call('pfCount', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function pfMerge()
    {
        return $this->call('pfMerge', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rawCommand()
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
}
