<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Session\Storage\Handler;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

/**
 * @deprecated Since 3.6. Use \Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler instead
 *
 * Redis based session storage with session locking support.
 *
 * @author Justin Rainbow <justin.rainbow@gmail.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 * @author Maurits van der Schee <maurits@vdschee.nl>
 * @author Pierre Boudelle <pierre.boudelle@gmail.com>
 */
class RedisSessionHandler extends AbstractSessionHandler
{
    /**
     * @var \Predis\Client|\Redis
     */
    protected $redis;

    /**
     * @var int Time to live in seconds
     */
    protected $ttl;

    /**
     * @var string Key prefix for shared environments
     */
    protected $prefix;

    /**
     * @var int Default PHP max execution time in seconds
     */
    const DEFAULT_MAX_EXECUTION_TIME = 30;

    /**
     * @var bool Indicates an sessions should be locked
     */
    protected $locking;

    /**
     * @var bool Indicates an active session lock
     */
    protected $locked;

    /**
     * @var string Session lock key
     */
    private $lockKey;

    /**
     * @var string Session lock token
     */
    private $token;

    /**
     * @var int Microseconds to wait between acquire lock tries
     */
    private $spinLockWait;

    /**
     * @var int Maximum amount of seconds to wait for the lock
     */
    protected $lockMaxWait;

    /**
     * Redis session storage constructor.
     *
     * @param \Predis\Client|\Redis $redis          Redis database connection
     * @param array                 $options        Session options
     * @param string                $prefix         Prefix to use when writing session data
     * @param bool                  $locking        Indicates an sessions should be locked
     * @param int                   $spinLockWait   Microseconds to wait between acquire lock tries
     */
    public function __construct($redis, array $options = array(), $prefix = 'session', $locking = true, $spinLockWait = 150000)
    {
        $this->redis = $redis;
        $this->ttl = isset($options['gc_maxlifetime']) ? (int) $options['gc_maxlifetime'] : 0;
        if (isset($options['cookie_lifetime']) && $options['cookie_lifetime'] > $this->ttl) {
            $this->ttl = (int) $options['cookie_lifetime'];
        }
        $this->prefix = $prefix;

        $this->locking = $locking;
        $this->locked = false;
        $this->spinLockWait = $spinLockWait;
        $this->lockMaxWait = ini_get('max_execution_time');
        if (!$this->lockMaxWait) {
            $this->lockMaxWait = self::DEFAULT_MAX_EXECUTION_TIME;
        }

        if (true === $locking) {
            register_shutdown_function(array($this, 'shutdown'));
        }
    }

    /**
     * Change the default TTL.
     *
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function close()
    {
        if ($this->locking && $this->locked) {
            $this->unlockSession();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function updateTimestamp($sessionId, $data)
    {
        if (0 < $this->ttl) {
            $this->redis->expire($this->getRedisKey($sessionId), $this->ttl);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|false
     */
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        // not required here because redis will auto expire the keys as long as ttl is set
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead($sessionId): string
    {
        if ($this->locking && !$this->locked && !$this->lockSession($sessionId)) {
            return false;
        }

        return $this->redis->get($this->getRedisKey($sessionId)) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($sessionId, $data): bool
    {
        if (0 < $this->ttl) {
            $this->redis->setex($this->getRedisKey($sessionId), $this->ttl, $data);
        } else {
            $this->redis->set($this->getRedisKey($sessionId), $data);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDestroy($sessionId): bool
    {
        $this->redis->del($this->getRedisKey($sessionId));
        $this->close();

        return true;
    }

    /**
     * Lock the session data.
     */
    protected function lockSession($sessionId)
    {
        $attempts = (1000000 / $this->spinLockWait) * $this->lockMaxWait;

        $this->token = uniqid();
        $this->lockKey = $this->getRedisKey($sessionId).'.lock';

        $setFunction = function ($redis, $key, $token, $ttl) {
            if ($redis instanceof \Redis) {
                return $redis->set(
                    $key,
                    $token,
                    array('NX', 'PX' => $ttl)
                );
            }

            return $redis->set(
                $key,
                $token,
                'PX',
                $ttl,
                'NX'
            );
        };

        for ($i = 0; $i < $attempts; ++$i) {
            // We try to aquire the lock
            $success = $setFunction($this->redis, $this->lockKey, $this->token, $this->lockMaxWait * 1000 + 1);
            if ($success) {
                $this->locked = true;

                return true;
            }

            usleep($this->spinLockWait);
        }

        return false;
    }

    /**
     * Unlock the session data.
     */
    private function unlockSession()
    {
        if ($this->redis instanceof \Redis) {
            // If we have the right token, then delete the lock
            $script = <<<LUA
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
LUA;

            $token = $this->redis->_serialize($this->token);
            $this->redis->eval($script, array($this->lockKey, $token), 1);
        } else {
            $this->redis->getProfile()->defineCommand('sncFreeSessionLock', FreeLockCommand::class);
            $this->redis->sncFreeSessionLock($this->lockKey, $this->token);
        }
        $this->locked = false;
        $this->token = null;
    }

    /**
     * Prepends the given key with a user-defined prefix (if any).
     *
     * @param string $key key
     *
     * @return string prefixed key
     */
    protected function getRedisKey($key)
    {
        if (empty($this->prefix)) {
            return $key;
        }

        return $this->prefix.$key;
    }

    /**
     * Shutdown handler, replacement for class destructor as it might not be called.
     */
    public function shutdown()
    {
        $this->close();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->shutdown();
    }
}
