<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Bundle\RedisBundle\Doctrine\Cache;

use Doctrine\Common\Cache\AbstractCache;

use Bundle\RedisBundle\Client\Predis\LoggingConnection;
use Predis\Commands\Get,
    Predis\Commands\Set,
    Predis\Commands\Delete,
    Predis\Commands\TimeToLive;

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
            $ttl = new TimeToLive();
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
    
    protected function _doExec($cmd)
    {
        $this->_redis->writeCommand($cmd);
        return $this->_redis->readResponse($cmd);
    }
}