<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\SwiftMailer;

/**
 * RedisSpool
 */
class RedisSpool extends \Swift_ConfigurableSpool
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var \Predis\Client|\Redis
     */
    protected $redis;

    /**
     * @param \Predis\Client|\Redis $redis
     */
    public function setRedis($redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function queueMessage(\Swift_Mime_Message $message)
    {
        $this->redis->rpush($this->key, serialize($message));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null)
    {
        if (!$this->redis->llen($this->key)) {
            return 0;
        }

        if (!$transport->isStarted()) {
            $transport->start();
        }

        $failedRecipients = (array) $failedRecipients;
        $count = 0;
        $time = time();

        while (($message = unserialize($this->redis->lpop($this->key)))) {
            $count += $transport->send($message, $failedRecipients);

            if ($this->getMessageLimit() && $count >= $this->getMessageLimit()) {
                break;
            }

            if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
                break;
            }
        }

        return $count;
    }
}
