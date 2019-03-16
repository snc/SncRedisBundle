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
class Client4_2_0 extends Redis
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
    public function _prefix($key)
    {
        return $this->call('_prefix', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function _serialize($value)
    {
        return $this->call('_serialize', array($value));
    }

    /**
     * {@inheritdoc}
     */
    public function _unserialize($value)
    {
        return $this->call('_unserialize', array($value));
    }

    /**
     * {@inheritdoc}
     */
    public function append($key, $value)
    {
        return $this->call('append', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function auth($password)
    {
        return $this->call('auth', array($password));
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
    public function bgrewriteaof()
    {
        return $this->call('bgrewriteaof');
    }

    /**
     * {@inheritdoc}
     */
    public function bitcount($key)
    {
        return $this->call('bitcount', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function bitop($operation, $ret_key, $key, ...$other_keys)
    {
        return $this->call('bitop', array($operation, $ret_key, $key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function bitpos($key, $bit, $start, $end)
    {
        return $this->call('bitpos', array($key, $bit, $start, $end));
    }

    /**
     * {@inheritdoc}
     */
    public function blPop($key, $timeout_or_key, ...$extra_args)
    {
        return $this->call('blPop', array($key, $timeout_or_key, $extra_args));
    }

    /**
     * {@inheritdoc}
     */
    public function brPop($key, $timeout_or_key, ...$extra_args)
    {
        return $this->call('brPop', array($key, $timeout_or_key, $extra_args));
    }

    /**
     * {@inheritdoc}
     */
    public function brpoplpush($src, $dst, $timeout)
    {
        return $this->call('brpoplpush', array($src, $dst, $timeout));
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
    public function client($cmd, ...$args)
    {
        return $this->call('client', array($cmd, $args));
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
    public function command(...$args)
    {
        return $this->call('command', array($args));
    }

    /**
     * {@inheritdoc}
     */
    public function config($cmd, $key, $value)
    {
        return $this->call('config', array($cmd, $key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function connect($host, $port, $timeout, $retry_interval)
    {
        return $this->call('connect', array($host, $port, $timeout, $retry_interval));
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
    public function debug($key)
    {
        return $this->call('debug', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function decr($key)
    {
        return $this->call('decr', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function decrBy($key, $value)
    {
        return $this->call('decrBy', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key, ...$other_keys)
    {
        return $this->call('delete', array($key, $other_keys));
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
    public function dump($key)
    {
        return $this->call('dump', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function eval($script, $args, $num_keys)
    {
        return $this->call('eval', array($script, $args, $num_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function evalsha($script_sha, $args, $num_keys)
    {
        return $this->call('evalsha', array($script_sha, $args, $num_keys));
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
    public function exists($key, ...$other_keys)
    {
        return $this->call('exists', array($key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function expireAt($key, $timestamp)
    {
        return $this->call('expireAt', array($key, $timestamp));
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll($async)
    {
        return $this->call('flushAll', array($async));
    }

    /**
     * {@inheritdoc}
     */
    public function flushDB($async)
    {
        return $this->call('flushDB', array($async));
    }

    /**
     * {@inheritdoc}
     */
    public function geoadd($key, $lng, $lat, $member, ...$other_triples)
    {
        return $this->call('geoadd', array($key, $lng, $lat, $member, $other_triples));
    }

    /**
     * {@inheritdoc}
     */
    public function geodist($key, $src, $dst, $unit)
    {
        return $this->call('geodist', array($key, $src, $dst, $unit));
    }

    /**
     * {@inheritdoc}
     */
    public function geohash($key, $member, ...$other_members)
    {
        return $this->call('geohash', array($key, $member, $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function geopos($key, $member, ...$other_members)
    {
        return $this->call('geopos', array($key, $member, $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function georadius($key, $lng, $lan, $radius, $unit, array $opts)
    {
        return $this->call('georadius', array($key, $lng, $lan, $radius, $unit, $opts));
    }

    /**
     * {@inheritdoc}
     */
    public function georadiusbymember($key, $member, $radius, $unit, array $opts)
    {
        return $this->call('georadiusbymember', array($key, $member, $radius, $unit, $opts));
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->call('get', array($key));
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
    public function getBit($key, $offset)
    {
        return $this->call('getBit', array($key, $offset));
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
    public function getHost()
    {
        return $this->call('getHost');
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys($pattern)
    {
        return $this->call('getKeys', array($pattern));
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
    public function getMode()
    {
        return $this->call('getMode');
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys)
    {
        return $this->call('getMultiple', array($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($option)
    {
        return $this->call('getOption', array($option));
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
    public function getPort()
    {
        return $this->call('getPort');
    }

    /**
     * {@inheritdoc}
     */
    public function getRange($key, $start, $end)
    {
        return $this->call('getRange', array($key, $start, $end));
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
    public function getSet($key, $value)
    {
        return $this->call('getSet', array($key, $value));
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
    public function hDel($key, $member, ...$other_members)
    {
        return $this->call('hDel', array($key, $member, $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function hExists($key, $member)
    {
        return $this->call('hExists', array($key, $member));
    }

    /**
     * {@inheritdoc}
     */
    public function hGet($key, $member)
    {
        return $this->call('hGet', array($key, $member));
    }

    /**
     * {@inheritdoc}
     */
    public function hGetAll($key)
    {
        return $this->call('hGetAll', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrBy($key, $member, $value)
    {
        return $this->call('hIncrBy', array($key, $member, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function hIncrByFloat($key, $member, $value)
    {
        return $this->call('hIncrByFloat', array($key, $member, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function hKeys($key)
    {
        return $this->call('hKeys', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function hLen($key)
    {
        return $this->call('hLen', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function hMget($key, array $keys)
    {
        return $this->call('hMget', array($key, $keys));
    }

    /**
     * {@inheritdoc}
     */
    public function hMset($key, array $pairs)
    {
        return $this->call('hMset', array($key, $pairs));
    }

    /**
     * {@inheritdoc}
     */
    public function hSet($key, $member, $value)
    {
        return $this->call('hSet', array($key, $member, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function hSetNx($key, $member, $value)
    {
        return $this->call('hSetNx', array($key, $member, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function hStrLen($key, $member)
    {
        return $this->call('hStrLen', array($key, $member));
    }

    /**
     * {@inheritdoc}
     */
    public function hVals($key)
    {
        return $this->call('hVals', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function hscan($str_key, &$i_iterator, $str_pattern, $i_count)
    {
        return $this->call('hscan', array($str_key, &$i_iterator, $str_pattern, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function incr($key)
    {
        return $this->call('incr', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function incrBy($key, $value)
    {
        return $this->call('incrBy', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function incrByFloat($key, $value)
    {
        return $this->call('incrByFloat', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function info($option)
    {
        return $this->call('info', array($option));
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
    public function lGet($key, $index)
    {
        return $this->call('lGet', array($key, $index));
    }

    /**
     * {@inheritdoc}
     */
    public function lGetRange($key, $start, $end)
    {
        return $this->call('lGetRange', array($key, $start, $end));
    }

    /**
     * {@inheritdoc}
     */
    public function lInsert($key, $position, $pivot, $value)
    {
        return $this->call('lInsert', array($key, $position, $pivot, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function lPop($key)
    {
        return $this->call('lPop', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function lPush($key, $value)
    {
        return $this->call('lPush', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function lPushx($key, $value)
    {
        return $this->call('lPushx', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function lRemove($key, $value, $count)
    {
        return $this->call('lRemove', array($key, $value, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function lSet($key, $index, $value)
    {
        return $this->call('lSet', array($key, $index, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function lSize($key)
    {
        return $this->call('lSize', array($key));
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
    public function listTrim($key, $start, $stop)
    {
        return $this->call('listTrim', array($key, $start, $stop));
    }

    /**
     * {@inheritdoc}
     */
    public function migrate($host, $port, $key, $db, $timeout, $copy, $replace)
    {
        return $this->call('migrate', array($host, $port, $key, $db, $timeout, $copy, $replace));
    }

    /**
     * {@inheritdoc}
     */
    public function move($key, $dbindex)
    {
        return $this->call('move', array($key, $dbindex));
    }

    /**
     * {@inheritdoc}
     */
    public function mset(array $pairs)
    {
        return $this->call('mset', array($pairs));
    }

    /**
     * {@inheritdoc}
     */
    public function msetnx(array $pairs)
    {
        return $this->call('msetnx', array($pairs));
    }

    /**
     * {@inheritdoc}
     */
    public function multi($mode)
    {
        return $this->call('multi', array($mode));
    }

    /**
     * {@inheritdoc}
     */
    public function object($field, $key)
    {
        return $this->call('object', array($field, $key));
    }

    /**
     * {@inheritdoc}
     */
    public function pconnect($host, $port, $timeout)
    {
        return $this->call('pconnect', array($host, $port, $timeout));
    }

    /**
     * {@inheritdoc}
     */
    public function persist($key)
    {
        return $this->call('persist', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function pexpire($key, $timestamp)
    {
        return $this->call('pexpire', array($key, $timestamp));
    }

    /**
     * {@inheritdoc}
     */
    public function pexpireAt($key, $timestamp)
    {
        return $this->call('pexpireAt', array($key, $timestamp));
    }

    /**
     * {@inheritdoc}
     */
    public function pfadd($key, array $elements)
    {
        return $this->call('pfadd', array($key, $elements));
    }

    /**
     * {@inheritdoc}
     */
    public function pfcount($key)
    {
        return $this->call('pfcount', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function pfmerge($dstkey, array $keys)
    {
        return $this->call('pfmerge', array($dstkey, $keys));
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
    public function pipeline()
    {
        return $this->call('pipeline');
    }

    /**
     * {@inheritdoc}
     */
    public function psetex($key, $expire, $value)
    {
        return $this->call('psetex', array($key, $expire, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function psubscribe(array $patterns)
    {
        return $this->call('psubscribe', array($patterns));
    }

    /**
     * {@inheritdoc}
     */
    public function pttl($key)
    {
        return $this->call('pttl', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function publish($channel, $message)
    {
        return $this->call('publish', array($channel, $message));
    }

    /**
     * {@inheritdoc}
     */
    public function pubsub($cmd, ...$args)
    {
        return $this->call('pubsub', array($cmd, $args));
    }

    /**
     * {@inheritdoc}
     */
    public function punsubscribe($pattern, ...$other_patterns)
    {
        return $this->call('punsubscribe', array($pattern, $other_patterns));
    }

    /**
     * {@inheritdoc}
     */
    public function rPop($key)
    {
        return $this->call('rPop', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function rPush($key, $value)
    {
        return $this->call('rPush', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function rPushx($key, $value)
    {
        return $this->call('rPushx', array($key, $value));
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
    public function rawcommand($cmd, ...$args)
    {
        return $this->call('rawcommand', array($cmd, $args));
    }

    /**
     * {@inheritdoc}
     */
    public function renameKey($key, $newkey)
    {
        return $this->call('renameKey', array($key, $newkey));
    }

    /**
     * {@inheritdoc}
     */
    public function renameNx($key, $newkey)
    {
        return $this->call('renameNx', array($key, $newkey));
    }

    /**
     * {@inheritdoc}
     */
    public function restore($ttl, $key, $value)
    {
        return $this->call('restore', array($ttl, $key, $value));
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
    public function rpoplpush($src, $dst)
    {
        return $this->call('rpoplpush', array($src, $dst));
    }

    /**
     * {@inheritdoc}
     */
    public function sAdd($key, $value)
    {
        return $this->call('sAdd', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function sAddArray($key, array $options)
    {
        return $this->call('sAddArray', array($key, $options));
    }

    /**
     * {@inheritdoc}
     */
    public function sContains($key, $value)
    {
        return $this->call('sContains', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function sDiff($key, ...$other_keys)
    {
        return $this->call('sDiff', array($key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sDiffStore($dst, $key, ...$other_keys)
    {
        return $this->call('sDiffStore', array($dst, $key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sInter($key, ...$other_keys)
    {
        return $this->call('sInter', array($key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sInterStore($dst, $key, ...$other_keys)
    {
        return $this->call('sInterStore', array($dst, $key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sMembers($key)
    {
        return $this->call('sMembers', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function sMove($src, $dst, $value)
    {
        return $this->call('sMove', array($src, $dst, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function sPop($key)
    {
        return $this->call('sPop', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function sRandMember($key, $count)
    {
        return $this->call('sRandMember', array($key, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function sRemove($key, $member, ...$other_members)
    {
        return $this->call('sRemove', array($key, $member, $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function sSize($key)
    {
        return $this->call('sSize', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function sUnion($key, ...$other_keys)
    {
        return $this->call('sUnion', array($key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function sUnionStore($dst, $key, ...$other_keys)
    {
        return $this->call('sUnionStore', array($dst, $key, $other_keys));
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
    public function scan(&$i_iterator, $str_pattern, $i_count)
    {
        return $this->call('scan', array(&$i_iterator, $str_pattern, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function script($cmd, ...$args)
    {
        return $this->call('script', array($cmd, $args));
    }

    /**
     * {@inheritdoc}
     */
    public function select($dbindex)
    {
        return $this->call('select', array($dbindex));
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $opts)
    {
        return $this->call('set', array($key, $value, $opts));
    }

    /**
     * {@inheritdoc}
     */
    public function setBit($key, $offset, $value)
    {
        return $this->call('setBit', array($key, $offset, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($option, $value)
    {
        return $this->call('setOption', array($option, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function setRange($key, $offset, $value)
    {
        return $this->call('setRange', array($key, $offset, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeout($key, $timeout)
    {
        return $this->call('setTimeout', array($key, $timeout));
    }

    /**
     * {@inheritdoc}
     */
    public function setex($key, $expire, $value)
    {
        return $this->call('setex', array($key, $expire, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function setnx($key, $value)
    {
        return $this->call('setnx', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function slaveof($host, $port)
    {
        return $this->call('slaveof', array($host, $port));
    }

    /**
     * {@inheritdoc}
     */
    public function slowlog($arg, $option)
    {
        return $this->call('slowlog', array($arg, $option));
    }

    /**
     * {@inheritdoc}
     */
    public function sort($key, array $options)
    {
        return $this->call('sort', array($key, $options));
    }

    /**
     * {@inheritdoc}
     */
    public function sortAsc($key, $pattern, $get, $start, $end, $getList)
    {
        return $this->call('sortAsc', array($key, $pattern, $get, $start, $end, $getList));
    }

    /**
     * {@inheritdoc}
     */
    public function sortAscAlpha($key, $pattern, $get, $start, $end, $getList)
    {
        return $this->call('sortAscAlpha', array($key, $pattern, $get, $start, $end, $getList));
    }

    /**
     * {@inheritdoc}
     */
    public function sortDesc($key, $pattern, $get, $start, $end, $getList)
    {
        return $this->call('sortDesc', array($key, $pattern, $get, $start, $end, $getList));
    }

    /**
     * {@inheritdoc}
     */
    public function sortDescAlpha($key, $pattern, $get, $start, $end, $getList)
    {
        return $this->call('sortDescAlpha', array($key, $pattern, $get, $start, $end, $getList));
    }

    /**
     * {@inheritdoc}
     */
    public function sscan($str_key, &$i_iterator, $str_pattern, $i_count)
    {
        return $this->call('sscan', array($str_key, &$i_iterator, $str_pattern, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function strlen($key)
    {
        return $this->call('strlen', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(array $channels)
    {
        return $this->call('subscribe', array($channels));
    }

    /**
     * {@inheritdoc}
     */
    public function swapdb($srcdb, $dstdb)
    {
        return $this->call('swapdb', array($srcdb, $dstdb));
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
    public function ttl($key)
    {
        return $this->call('ttl', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function type($key)
    {
        return $this->call('type', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function unlink($key, ...$other_keys)
    {
        return $this->call('unlink', array($key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribe($channel, ...$other_channels)
    {
        return $this->call('unsubscribe', array($channel, $other_channels));
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
    public function wait($numslaves, $timeout)
    {
        return $this->call('wait', array($numslaves, $timeout));
    }

    /**
     * {@inheritdoc}
     */
    public function watch($key, ...$other_keys)
    {
        return $this->call('watch', array($key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function xack($str_key, $str_group, array $arr_ids)
    {
        return $this->call('xack', array($str_key, $str_group, $arr_ids));
    }

    /**
     * {@inheritdoc}
     */
    public function xadd($str_key, $str_id, array $arr_fields, $i_maxlen, $boo_approximate)
    {
        return $this->call('xadd', array($str_key, $str_id, $arr_fields, $i_maxlen, $boo_approximate));
    }

    /**
     * {@inheritdoc}
     */
    public function xclaim($str_key, $str_group, $str_consumer, $i_min_idle, array $arr_ids, array $arr_opts)
    {
        return $this->call('xclaim', array($str_key, $str_group, $str_consumer, $i_min_idle, $arr_ids, $arr_opts));
    }

    /**
     * {@inheritdoc}
     */
    public function xdel($str_key, array $arr_ids)
    {
        return $this->call('xdel', array($str_key, $arr_ids));
    }

    /**
     * {@inheritdoc}
     */
    public function xgroup($str_operation, $str_key, $str_arg1, $str_arg2, $str_arg3)
    {
        return $this->call('xgroup', array($str_operation, $str_key, $str_arg1, $str_arg2, $str_arg3));
    }

    /**
     * {@inheritdoc}
     */
    public function xinfo($str_cmd, $str_key, $str_group)
    {
        return $this->call('xinfo', array($str_cmd, $str_key, $str_group));
    }

    /**
     * {@inheritdoc}
     */
    public function xlen($key)
    {
        return $this->call('xlen', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function xpending($str_key, $str_group, $str_start, $str_end, $i_count, $str_consumer)
    {
        return $this->call('xpending', array($str_key, $str_group, $str_start, $str_end, $i_count, $str_consumer));
    }

    /**
     * {@inheritdoc}
     */
    public function xrange($str_key, $str_start, $str_end, $i_count)
    {
        return $this->call('xrange', array($str_key, $str_start, $str_end, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function xread(array $arr_streams, $i_count, $i_block)
    {
        return $this->call('xread', array($arr_streams, $i_count, $i_block));
    }

    /**
     * {@inheritdoc}
     */
    public function xreadgroup($str_group, $str_consumer, array $arr_streams, $i_count, $i_block)
    {
        return $this->call('xreadgroup', array($str_group, $str_consumer, $arr_streams, $i_count, $i_block));
    }

    /**
     * {@inheritdoc}
     */
    public function xrevrange($str_key, $str_start, $str_end, $i_count)
    {
        return $this->call('xrevrange', array($str_key, $str_start, $str_end, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function xtrim($str_key, $i_maxlen, $boo_approximate)
    {
        return $this->call('xtrim', array($str_key, $i_maxlen, $boo_approximate));
    }

    /**
     * {@inheritdoc}
     */
    public function zAdd($key, $score, $value)
    {
        return $this->call('zAdd', array($key, $score, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function zCard($key)
    {
        return $this->call('zCard', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function zCount($key, $min, $max)
    {
        return $this->call('zCount', array($key, $min, $max));
    }

    /**
     * {@inheritdoc}
     */
    public function zDelete($key, $member, ...$other_members)
    {
        return $this->call('zDelete', array($key, $member, $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByRank($key, $start, $end)
    {
        return $this->call('zDeleteRangeByRank', array($key, $start, $end));
    }

    /**
     * {@inheritdoc}
     */
    public function zDeleteRangeByScore($key, $min, $max)
    {
        return $this->call('zDeleteRangeByScore', array($key, $min, $max));
    }

    /**
     * {@inheritdoc}
     */
    public function zIncrBy($key, $value, $member)
    {
        return $this->call('zIncrBy', array($key, $value, $member));
    }

    /**
     * {@inheritdoc}
     */
    public function zInter($key, array $keys, array $weights, $aggregate)
    {
        return $this->call('zInter', array($key, $keys, $weights, $aggregate));
    }

    /**
     * {@inheritdoc}
     */
    public function zLexCount($key, $min, $max)
    {
        return $this->call('zLexCount', array($key, $min, $max));
    }

    /**
     * {@inheritdoc}
     */
    public function zRange($key, $start, $end, $scores)
    {
        return $this->call('zRange', array($key, $start, $end, $scores));
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByLex($key, $min, $max, $offset, $limit)
    {
        return $this->call('zRangeByLex', array($key, $min, $max, $offset, $limit));
    }

    /**
     * {@inheritdoc}
     */
    public function zRangeByScore($key, $start, $end, array $options)
    {
        return $this->call('zRangeByScore', array($key, $start, $end, $options));
    }

    /**
     * {@inheritdoc}
     */
    public function zRank($key, $member)
    {
        return $this->call('zRank', array($key, $member));
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByLex($key, $min, $max)
    {
        return $this->call('zRemRangeByLex', array($key, $min, $max));
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRange($key, $start, $end, $scores)
    {
        return $this->call('zRevRange', array($key, $start, $end, $scores));
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByLex($key, $min, $max, $offset, $limit)
    {
        return $this->call('zRevRangeByLex', array($key, $min, $max, $offset, $limit));
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRangeByScore($key, $start, $end, array $options)
    {
        return $this->call('zRevRangeByScore', array($key, $start, $end, $options));
    }

    /**
     * {@inheritdoc}
     */
    public function zRevRank($key, $member)
    {
        return $this->call('zRevRank', array($key, $member));
    }

    /**
     * {@inheritdoc}
     */
    public function zScore($key, $member)
    {
        return $this->call('zScore', array($key, $member));
    }

    /**
     * {@inheritdoc}
     */
    public function zUnion($key, array $keys, array $weights, $aggregate)
    {
        return $this->call('zUnion', array($key, $keys, $weights, $aggregate));
    }

    /**
     * {@inheritdoc}
     */
    public function zscan($str_key, &$i_iterator, $str_pattern, $i_count)
    {
        return $this->call('zscan', array($str_key, &$i_iterator, $str_pattern, $i_count));
    }

    /**
     * {@inheritdoc}
     */
    public function del($key, ...$other_keys)
    {
        return $this->call('del', array($key, $other_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate($script, $args, $num_keys)
    {
        return $this->call('evaluate', array($script, $args, $num_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateSha($script_sha, $args, $num_keys)
    {
        return $this->call('evaluateSha', array($script_sha, $args, $num_keys));
    }

    /**
     * {@inheritdoc}
     */
    public function expire($key, $timeout)
    {
        return $this->call('expire', array($key, $timeout));
    }

    /**
     * {@inheritdoc}
     */
    public function keys($pattern)
    {
        return $this->call('keys', array($pattern));
    }

    /**
     * {@inheritdoc}
     */
    public function lLen($key)
    {
        return $this->call('lLen', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function lindex($key, $index)
    {
        return $this->call('lindex', array($key, $index));
    }

    /**
     * {@inheritdoc}
     */
    public function lrange($key, $start, $end)
    {
        return $this->call('lrange', array($key, $start, $end));
    }

    /**
     * {@inheritdoc}
     */
    public function lrem($key, $value, $count)
    {
        return $this->call('lrem', array($key, $value, $count));
    }

    /**
     * {@inheritdoc}
     */
    public function ltrim($key, $start, $stop)
    {
        return $this->call('ltrim', array($key, $start, $stop));
    }

    /**
     * {@inheritdoc}
     */
    public function mget(array $keys)
    {
        return $this->call('mget', array($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function open($host, $port, $timeout, $retry_interval)
    {
        return $this->call('open', array($host, $port, $timeout, $retry_interval));
    }

    /**
     * {@inheritdoc}
     */
    public function popen($host, $port, $timeout)
    {
        return $this->call('popen', array($host, $port, $timeout));
    }

    /**
     * {@inheritdoc}
     */
    public function rename($key, $newkey)
    {
        return $this->call('rename', array($key, $newkey));
    }

    /**
     * {@inheritdoc}
     */
    public function sGetMembers($key)
    {
        return $this->call('sGetMembers', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function scard($key)
    {
        return $this->call('scard', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function sendEcho($msg)
    {
        return $this->call('sendEcho', array($msg));
    }

    /**
     * {@inheritdoc}
     */
    public function sismember($key, $value)
    {
        return $this->call('sismember', array($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function srem($key, $member, ...$other_members)
    {
        return $this->call('srem', array($key, $member, $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function substr($key, $start, $end)
    {
        return $this->call('substr', array($key, $start, $end));
    }

    /**
     * {@inheritdoc}
     */
    public function zRem($key, $member, ...$other_members)
    {
        return $this->call('zRem', array($key, $member, $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByRank($key, $min, $max)
    {
        return $this->call('zRemRangeByRank', array($key, $min, $max));
    }

    /**
     * {@inheritdoc}
     */
    public function zRemRangeByScore($key, $min, $max)
    {
        return $this->call('zRemRangeByScore', array($key, $min, $max));
    }

    /**
     * {@inheritdoc}
     */
    public function zRemove($key, $member, ...$other_members)
    {
        return $this->call('zRemove', array($key, $member, $other_members));
    }

    /**
     * {@inheritdoc}
     */
    public function zRemoveRangeByScore($key, $min, $max)
    {
        return $this->call('zRemoveRangeByScore', array($key, $min, $max));
    }

    /**
     * {@inheritdoc}
     */
    public function zReverseRange($key, $start, $end, $scores)
    {
        return $this->call('zReverseRange', array($key, $start, $end, $scores));
    }

    /**
     * {@inheritdoc}
     */
    public function zSize($key)
    {
        return $this->call('zSize', array($key));
    }

    /**
     * {@inheritdoc}
     */
    public function zinterstore($key, array $keys, array $weights, $aggregate)
    {
        return $this->call('zinterstore', array($key, $keys, $weights, $aggregate));
    }

    /**
     * {@inheritdoc}
     */
    public function zunionstore($key, array $keys, array $weights, $aggregate)
    {
        return $this->call('zunionstore', array($key, $keys, $weights, $aggregate));
    }
}
