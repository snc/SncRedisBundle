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
class Client3_1_6 extends Redis
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

        if (null !== $this->logger) {
            $this->logger->logCommand($this->getCommandString($name, $arguments), $duration, $this->alias, false);
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
    public function __destruct()
    {
        return $this->call('__destruct');
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        return $this->call('connect');
    }

    /**
     * {@inheritdoc}
     */
    public function pconnect()
    {
        return $this->call('pconnect');
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return $this->call('close');
    }

    /**
     * {@inheritdoc}
     */
    public function ping()
    {
        return $this->call('ping');
    }

    /**
     * {@inheritdoc}
     */
    public function echo()
    {
        return $this->call('echo');
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->call('get');
    }

    /**
     * {@inheritdoc}
     */
    public function set()
    {
        return $this->call('set');
    }

    /**
     * {@inheritdoc}
     */
    public function setex()
    {
        return $this->call('setex');
    }

    /**
     * {@inheritdoc}
     */
    public function psetex()
    {
        return $this->call('psetex');
    }

    /**
     * {@inheritdoc}
     */
    public function setnx()
    {
        return $this->call('setnx');
    }

    /**
     * {@inheritdoc}
     */
    public function getSet()
    {
        return $this->call('getSet');
    }

    /**
     * {@inheritdoc}
     */
    public function randomKey()
    {
        return $this->call('randomKey');
    }

    /**
     * {@inheritdoc}
     */
    public function renameKey()
    {
        return $this->call('renameKey');
    }

    /**
     * {@inheritdoc}
     */
    public function renameNx()
    {
        return $this->call('renameNx');
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple()
    {
        return $this->call('getMultiple');
    }

    /**
     * {@inheritdoc}
     */
    public function exists()
    {
        return $this->call('exists');
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        return $this->call('delete');
    }

    /**
     * {@inheritdoc}
     */
    public function incr()
    {
        return $this->call('incr');
    }

    /**
     * {@inheritdoc}
     */
    public function incrBy()
    {
        return $this->call('incrBy');
    }

    /**
     * {@inheritdoc}
     */
    public function incrByFloat()
    {
        return $this->call('incrByFloat');
    }

    /**
     * {@inheritdoc}
     */
    public function decr()
    {
        return $this->call('decr');
    }

    /**
     * {@inheritdoc}
     */
    public function decrBy()
    {
        return $this->call('decrBy');
    }

    /**
     * {@inheritdoc}
     */
    public function type()
    {
        return $this->call('type');
    }

    /**
     * {@inheritdoc}
     */
    public function append()
    {
        return $this->call('append');
    }

    /**
     * {@inheritdoc}
     */
    public function getRange()
    {
        return $this->call('getRange');
    }

    /**
     * {@inheritdoc}
     */
    public function setRange()
    {
        return $this->call('setRange');
    }

    /**
     * {@inheritdoc}
     */
    public function getBit()
    {
        return $this->call('getBit');
    }

    /**
     * {@inheritdoc}
     */
    public function setBit()
    {
        return $this->call('setBit');
    }

    /**
     * {@inheritdoc}
     */
    public function strlen()
    {
        return $this->call('strlen');
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        return $this->call('getKeys');
    }

    /**
     * {@inheritdoc}
     */
    public function sort()
    {
        return $this->call('sort');
    }

    /**
     * {@inheritdoc}
     */
    public function sortAsc()
    {
        return $this->call('sortAsc');
    }

    /**
     * {@inheritdoc}
     */
    public function sortAscAlpha()
    {
        return $this->call('sortAscAlpha');
    }

    /**
     * {@inheritdoc}
     */
    public function sortDesc()
    {
        return $this->call('sortDesc');
    }

    /**
     * {@inheritdoc}
     */
    public function sortDescAlpha()
    {
        return $this->call('sortDescAlpha');
    }

    /**
     * {@inheritdoc}
     */
    public function lPush()
    {
        return $this->call('lPush');
    }

    /**
     * {@inheritdoc}
     */
    public function rPush()
    {
        return $this->call('rPush');
    }

    /**
     * {@inheritdoc}
     */
    public function lPushx()
    {
        return $this->call('lPushx');
    }

    /**
     * {@inheritdoc}
     */
    public function rPushx()
    {
        return $this->call('rPushx');
    }

    /**
     * {@inheritdoc}
     */
    public function lPop()
    {
        return $this->call('lPop');
    }

    /**
     * {@inheritdoc}
     */
    public function rPop()
    {
        return $this->call('rPop');
    }

    /**
     * {@inheritdoc}
     */
    public function blPop()
    {
        return $this->call('blPop');
    }

    /**
     * {@inheritdoc}
     */
    public function brPop()
    {
        return $this->call('brPop');
    }

    /**
     * {@inheritdoc}
     */
    public function lSize()
    {
        return $this->call('lSize');
    }

    /**
     * {@inheritdoc}
     */
    public function lRemove()
    {
        return $this->call('lRemove');
    }

    /**
     * {@inheritdoc}
     */
    public function listTrim()
    {
        return $this->call('listTrim');
    }

    /**
     * {@inheritdoc}
     */
    public function lGet()
    {
        return $this->call('lGet');
    }

    /**
     * {@inheritdoc}
     */
    public function lGetRange()
    {
        return $this->call('lGetRange');
    }

    /**
     * {@inheritdoc}
     */
    public function lSet()
    {
        return $this->call('lSet');
    }

    /**
     * {@inheritdoc}
     */
    public function lInsert()
    {
        return $this->call('lInsert');
    }

    /**
     * {@inheritdoc}
     */
    public function sAdd()
    {
        return $this->call('sAdd');
    }

    /**
     * {@inheritdoc}
     */
    public function sAddArray()
    {
        return $this->call('sAddArray');
    }

    /**
     * {@inheritdoc}
     */
    public function sSize()
    {
        return $this->call('sSize');
    }

    /**
     * {@inheritdoc}
     */
    public function sRemove()
    {
        return $this->call('sRemove');
    }

    /**
     * {@inheritdoc}
     */
    public function sMove()
    {
        return $this->call('sMove');
    }

    /**
     * {@inheritdoc}
     */
    public function sPop()
    {
        return $this->call('sPop');
    }

    /**
     * {@inheritdoc}
     */
    public function sRandMember()
    {
        return $this->call('sRandMember');
    }

    /**
     * {@inheritdoc}
     */
    public function sContains()
    {
        return $this->call('sContains');
    }

    /**
     * {@inheritdoc}
     */
    public function sMembers()
    {
        return $this->call('sMembers');
    }

    /**
     * {@inheritdoc}
     */
    public function sInter()
    {
        return $this->call('sInter');
    }

    /**
     * {@inheritdoc}
     */
    public function sInterStore()
    {
        return $this->call('sInterStore');
    }

    /**
     * {@inheritdoc}
     */
    public function sUnion()
    {
        return $this->call('sUnion');
    }

    /**
     * {@inheritdoc}
     */
    public function sUnionStore()
    {
        return $this->call('sUnionStore');
    }

    /**
     * {@inheritdoc}
     */
    public function sDiff()
    {
        return $this->call('sDiff');
    }

    /**
     * {@inheritdoc}
     */
    public function sDiffStore()
    {
        return $this->call('sDiffStore');
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeout()
    {
        return $this->call('setTimeout');
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        return $this->call('save');
    }

    /**
     * {@inheritdoc}
     */
    public function bgSave()
    {
        return $this->call('bgSave');
    }

    /**
     * {@inheritdoc}
     */
    public function lastSave()
    {
        return $this->call('lastSave');
    }

    /**
     * {@inheritdoc}
     */
    public function flushDB()
    {
        return $this->call('flushDB');
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->call('flushAll');
    }

    /**
     * {@inheritdoc}
     */
    public function dbSize()
    {
        return $this->call('dbSize');
    }

    /**
     * {@inheritdoc}
     */
    public function auth()
    {
        return $this->call('auth');
    }

    /**
     * {@inheritdoc}
     */
    public function ttl()
    {
        return $this->call('ttl');
    }

    /**
     * {@inheritdoc}
     */
    public function pttl()
    {
        return $this->call('pttl');
    }

    /**
     * {@inheritdoc}
     */
    public function persist()
    {
        return $this->call('persist');
    }

    /**
     * {@inheritdoc}
     */
    public function info()
    {
        return $this->call('info');
    }

    /**
     * {@inheritdoc}
     */
    public function select()
    {
        return $this->call('select');
    }

    /**
     * {@inheritdoc}
     */
    public function move()
    {
        return $this->call('move');
    }

    /**
     * {@inheritdoc}
     */
    public function bgrewriteaof()
    {
        return $this->call('bgrewriteaof');
    }

    /**
     * {@inheritdoc}
     */
    public function slaveof()
    {
        return $this->call('slaveof');
    }

    /**
     * {@inheritdoc}
     */
    public function object()
    {
        return $this->call('object');
    }

    /**
     * {@inheritdoc}
     */
    public function bitop()
    {
        return $this->call('bitop');
    }

    /**
     * {@inheritdoc}
     */
    public function bitcount()
    {
        return $this->call('bitcount');
    }

    /**
     * {@inheritdoc}
     */
    public function bitpos()
    {
        return $this->call('bitpos');
    }

    /**
     * {@inheritdoc}
     */
    public function mset()
    {
        return $this->call('mset');
    }

    /**
     * {@inheritdoc}
     */
    public function msetnx()
    {
        return $this->call('msetnx');
    }

    /**
     * {@inheritdoc}
     */
    public function rpoplpush()
    {
        return $this->call('rpoplpush');
    }

    /**
     * {@inheritdoc}
     */
    public function brpoplpush()
    {
        return $this->call('brpoplpush');
    }

    /**
     * {@inheritdoc}
     */
    public function zAdd()
    {
        return $this->call('zAdd');
    }

    /**
     * {@inheritdoc}
     */
    public function zDelete()
    {
        return $this->call('zDelete');
    }

    /**
     * {@inheritdoc}
     */
    public function zRange()
    {
        return $this->call('zRange');
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRange()
    {
        return $this->call('zRevRange');
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByScore()
    {
        return $this->call('zRangeByScore');
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByScore()
    {
        return $this->call('zRevRangeByScore');
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByLex()
    {
        return $this->call('zRangeByLex');
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByLex()
    {
        return $this->call('zRevRangeByLex');
    }

    /**
     * {@inheritdoc}
     */
    public function zLexCount()
    {
        return $this->call('zLexCount');
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByLex()
    {
        return $this->call('zRemRangeByLex');
    }

    /**
     * {@inheritdoc}
     */
    public function zCount()
    {
        return $this->call('zCount');
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByScore()
    {
        return $this->call('zDeleteRangeByScore');
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByRank()
    {
        return $this->call('zDeleteRangeByRank');
    }

    /**
     * {@inheritdoc}
     */
    public function zCard()
    {
        return $this->call('zCard');
    }

    /**
     * {@inheritdoc}
     */
    public function zScore()
    {
        return $this->call('zScore');
    }

    /**
     * {@inheritdoc}
     */
    public function zRank()
    {
        return $this->call('zRank');
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRank()
    {
        return $this->call('zRevRank');
    }

    /**
     * {@inheritdoc}
     */
    public function zInter()
    {
        return $this->call('zInter');
    }

    /**
     * {@inheritdoc}
     */
    public function zUnion()
    {
        return $this->call('zUnion');
    }

    /**
     * {@inheritdoc}
     */
    public function zIncrBy()
    {
        return $this->call('zIncrBy');
    }

    /**
     * {@inheritdoc}
     */
    public function expireAt()
    {
        return $this->call('expireAt');
    }

    /**
     * {@inheritdoc}
     */
    public function pexpire()
    {
        return $this->call('pexpire');
    }

    /**
     * {@inheritdoc}
     */
    public function pexpireAt()
    {
        return $this->call('pexpireAt');
    }

    /**
     * {@inheritdoc}
     */
    public function hGet()
    {
        return $this->call('hGet');
    }

    /**
     * {@inheritdoc}
     */
    public function hSet()
    {
        return $this->call('hSet');
    }

    /**
     * {@inheritdoc}
     */
    public function hSetNx()
    {
        return $this->call('hSetNx');
    }

    /**
     * {@inheritdoc}
     */
    public function hDel()
    {
        return $this->call('hDel');
    }

    /**
     * {@inheritdoc}
     */
    public function hLen()
    {
        return $this->call('hLen');
    }

    /**
     * {@inheritdoc}
     */
    public function hKeys()
    {
        return $this->call('hKeys');
    }

    /**
     * {@inheritdoc}
     */
    public function hVals()
    {
        return $this->call('hVals');
    }

    /**
     * {@inheritdoc}
     */
    public function hGetAll()
    {
        return $this->call('hGetAll');
    }

    /**
     * {@inheritdoc}
     */
    public function hExists()
    {
        return $this->call('hExists');
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrBy()
    {
        return $this->call('hIncrBy');
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrByFloat()
    {
        return $this->call('hIncrByFloat');
    }

    /**
     * {@inheritdoc}
     */
    public function hMset()
    {
        return $this->call('hMset');
    }

    /**
     * {@inheritdoc}
     */
    public function hMget()
    {
        return $this->call('hMget');
    }

    /**
     * {@inheritdoc}
     */
    public function hStrLen()
    {
        return $this->call('hStrLen');
    }

    /**
     * {@inheritdoc}
     */
    public function multi()
    {
        return $this->call('multi');
    }

    /**
     * {@inheritdoc}
     */
    public function discard()
    {
        return $this->call('discard');
    }

    /**
     * {@inheritdoc}
     */
    public function exec()
    {
        return $this->call('exec');
    }

    /**
     * {@inheritdoc}
     */
    public function pipeline()
    {
        return $this->call('pipeline');
    }

    /**
     * {@inheritdoc}
     */
    public function watch()
    {
        return $this->call('watch');
    }

    /**
     * {@inheritdoc}
     */
    public function unwatch()
    {
        return $this->call('unwatch');
    }

    /**
     * {@inheritdoc}
     */
    public function publish()
    {
        return $this->call('publish');
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe()
    {
        return $this->call('subscribe');
    }

    /**
     * {@inheritdoc}
     */
    public function psubscribe()
    {
        return $this->call('psubscribe');
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribe()
    {
        return $this->call('unsubscribe');
    }

    /**
     * {@inheritdoc}
     */
    public function punsubscribe()
    {
        return $this->call('punsubscribe');
    }

    /**
     * {@inheritdoc}
     */
    public function time()
    {
        return $this->call('time');
    }

    /**
     * {@inheritdoc}
     */
    public function role()
    {
        return $this->call('role');
    }

    /**
     * {@inheritdoc}
     */
    public function eval()
    {
        return $this->call('eval');
    }

    /**
     * {@inheritdoc}
     */
    public function evalsha()
    {
        return $this->call('evalsha');
    }

    /**
     * {@inheritdoc}
     */
    public function script()
    {
        return $this->call('script');
    }

    /**
     * {@inheritdoc}
     */
    public function debug()
    {
        return $this->call('debug');
    }

    /**
     * {@inheritdoc}
     */
    public function dump()
    {
        return $this->call('dump');
    }

    /**
     * {@inheritdoc}
     */
    public function restore()
    {
        return $this->call('restore');
    }

    /**
     * {@inheritdoc}
     */
    public function migrate()
    {
        return $this->call('migrate');
    }

    /**
     * {@inheritdoc}
     */
    public function getLastError()
    {
        return $this->call('getLastError');
    }

    /**
     * {@inheritdoc}
     */
    public function clearLastError()
    {
        return $this->call('clearLastError');
    }

    /**
     * {@inheritdoc}
     */
    public function _prefix()
    {
        return $this->call('_prefix');
    }

    /**
     * {@inheritdoc}
     */
    public function _serialize()
    {
        return $this->call('_serialize');
    }

    /**
     * {@inheritdoc}
     */
    public function _unserialize()
    {
        return $this->call('_unserialize');
    }

    /**
     * {@inheritdoc}
     */
    public function client()
    {
        return $this->call('client');
    }

    /**
     * {@inheritdoc}
     */
    public function command()
    {
        return $this->call('command');
    }

    /**
     * {@inheritdoc}
     */
    public function scan(&$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->call('scan', array(&$i_iterator, $str_pattern, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function hscan($str_key, &$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->call('hscan', array($str_key, &$i_iterator, $str_pattern, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function zscan($str_key, &$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->call('zscan', array($str_key, &$i_iterator, $str_pattern, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function sscan($str_key, &$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->call('sscan', array($str_key, &$i_iterator, $str_pattern, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function pfadd()
    {
        return $this->call('pfadd');
    }

    /**
     * {@inheritdoc}
     */
    public function pfcount()
    {
        return $this->call('pfcount');
    }

    /**
     * {@inheritdoc}
     */
    public function pfmerge()
    {
        return $this->call('pfmerge');
    }

    /**
     * {@inheritdoc}
     */
    public function getOption()
    {
        return $this->call('getOption');
    }

    /**
     * {@inheritdoc}
     */
    public function setOption()
    {
        return $this->call('setOption');
    }

    /**
     * {@inheritdoc}
     */
    public function config()
    {
        return $this->call('config');
    }

    /**
     * {@inheritdoc}
     */
    public function slowlog()
    {
        return $this->call('slowlog');
    }

    /**
     * {@inheritdoc}
     */
    public function rawcommand()
    {
        return $this->call('rawcommand');
    }

    /**
     * {@inheritdoc}
     */
    public function geoadd()
    {
        return $this->call('geoadd');
    }

    /**
     * {@inheritdoc}
     */
    public function geohash()
    {
        return $this->call('geohash');
    }

    /**
     * {@inheritdoc}
     */
    public function geopos()
    {
        return $this->call('geopos');
    }

    /**
     * {@inheritdoc}
     */
    public function geodist()
    {
        return $this->call('geodist');
    }

    /**
     * {@inheritdoc}
     */
    public function georadius()
    {
        return $this->call('georadius');
    }

    /**
     * {@inheritdoc}
     */
    public function georadiusbymember()
    {
        return $this->call('georadiusbymember');
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->call('getHost');
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->call('getPort');
    }

    /**
     * {@inheritdoc}
     */
    public function getDBNum()
    {
        return $this->call('getDBNum');
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeout()
    {
        return $this->call('getTimeout');
    }

    /**
     * {@inheritdoc}
     */
    public function getReadTimeout()
    {
        return $this->call('getReadTimeout');
    }

    /**
     * {@inheritdoc}
     */
    public function getPersistentID()
    {
        return $this->call('getPersistentID');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuth()
    {
        return $this->call('getAuth');
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->call('isConnected');
    }

    /**
     * {@inheritdoc}
     */
    public function getMode()
    {
        return $this->call('getMode');
    }

    /**
     * {@inheritdoc}
     */
    public function wait()
    {
        return $this->call('wait');
    }

    /**
     * {@inheritdoc}
     */
    public function pubsub()
    {
        return $this->call('pubsub');
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        return $this->call('open');
    }

    /**
     * {@inheritdoc}
     */
    public function popen()
    {
        return $this->call('popen');
    }

    /**
     * {@inheritdoc}
     */
    public function lLen()
    {
        return $this->call('lLen');
    }

    /**
     * {@inheritdoc}
     */
    public function sGetMembers()
    {
        return $this->call('sGetMembers');
    }

    /**
     * {@inheritdoc}
     */
    public function mget()
    {
        return $this->call('mget');
    }

    /**
     * {@inheritdoc}
     */
    public function expire()
    {
        return $this->call('expire');
    }

    /**
     * {@inheritdoc}
     */
    public function zunionstore()
    {
        return $this->call('zunionstore');
    }

    /**
     * {@inheritdoc}
     */
    public function zinterstore()
    {
        return $this->call('zinterstore');
    }

    /**
     * {@inheritdoc}
     */
    public function zRemove()
    {
        return $this->call('zRemove');
    }

    /**
     * {@inheritdoc}
     */
    public function zRem()
    {
        return $this->call('zRem');
    }

    /**
     * {@inheritdoc}
     */
    public function zRemoveRangeByScore()
    {
        return $this->call('zRemoveRangeByScore');
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByScore()
    {
        return $this->call('zRemRangeByScore');
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByRank()
    {
        return $this->call('zRemRangeByRank');
    }

    /**
     * {@inheritdoc}
     */
    public function zSize()
    {
        return $this->call('zSize');
    }

    /**
     * {@inheritdoc}
     */
    public function substr()
    {
        return $this->call('substr');
    }

    /**
     * {@inheritdoc}
     */
    public function rename()
    {
        return $this->call('rename');
    }

    /**
     * {@inheritdoc}
     */
    public function del()
    {
        return $this->call('del');
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return $this->call('keys');
    }

    /**
     * {@inheritdoc}
     */
    public function lrem()
    {
        return $this->call('lrem');
    }

    /**
     * {@inheritdoc}
     */
    public function ltrim()
    {
        return $this->call('ltrim');
    }

    /**
     * {@inheritdoc}
     */
    public function lindex()
    {
        return $this->call('lindex');
    }

    /**
     * {@inheritdoc}
     */
    public function lrange()
    {
        return $this->call('lrange');
    }

    /**
     * {@inheritdoc}
     */
    public function scard()
    {
        return $this->call('scard');
    }

    /**
     * {@inheritdoc}
     */
    public function srem()
    {
        return $this->call('srem');
    }

    /**
     * {@inheritdoc}
     */
    public function sismember()
    {
        return $this->call('sismember');
    }

    /**
     * {@inheritdoc}
     */
    public function zReverseRange()
    {
        return $this->call('zReverseRange');
    }

    /**
     * {@inheritdoc}
     */
    public function sendEcho()
    {
        return $this->call('sendEcho');
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate()
    {
        return $this->call('evaluate');
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateSha()
    {
        return $this->call('evaluateSha');
    }
}
