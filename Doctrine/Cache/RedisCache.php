<?php

namespace Bundle\RedisBundle\Doctrine\Cache;

use Doctrine\Common\Cache\AbstractCache;

use Bundle\RedisBundle\Client\Predis\LoggingConnection;
use Predis\Commands\Get,
    Predis\Commands\Set,
    Predis\Commands\Delete,
    Predis\Commands\TimeToLive,
    Predis\Commands\Expires;

/**
 * Redis cache driver.
 *
 * @link    http://github.com/justinrainbow/
 * @author  Justin Rainbow <justin.rainbow@gmail.com>
 */
class RedisCache extends AbstractCache
{
    /**
     * @var Redis
     */
    private $_redis;

    /**
     * Sets the redis instance to use.
     *
     * @param Redis $redis
     */
    public function setRedisConnection(LoggingConnection $redis)
    {
        $this->_redis = $redis;
    }

    /**
     * Gets the redis instance used by the cache.
     *
     * @return Redis
     */
    public function getRedis()
    {
        return $this->_redis;
    }

    /**
     * {@inheritdoc}
     */
    public function getIds()
    {
        throw new \RuntimeException('Method not yet implemented.');
    }

    /**
     * {@inheritdoc}
     */
    protected function _doFetch($id)
    {
        $cmd = new Get();
        $cmd->setArgumentsArray(array($id));

        return unserialize($this->_doExec($cmd));
    }

    /**
     * {@inheritdoc}
     */
    protected function _doContains($id)
    {
        return (bool) $this->_doFetch($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function _doSave($id, $data, $lifeTime = 0)
    {
        $set = new Set();
        $set->setArgumentsArray(array($id, serialize($data)));
        $result = $this->_doExec($set);
        
        if ($lifeTime > 0) {
            $ttl = new Expires();
            $ttl->setArgumentsArray(array($id, (int) $lifeTime));
            $this->_doExec($ttl);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function _doDelete($id)
    {
        return $this->_redis->delete($id);
    }
    
    /**
     * Perform both the writeCommand and readResponse for a
     * given Redis Command.
     * 
     * @param  object  $cmd  Redis command object
     * 
     * @return mixed response
     */
    protected function _doExec($cmd)
    {
        $this->_redis->writeCommand($cmd);
        return $this->_redis->readResponse($cmd);
    }
}