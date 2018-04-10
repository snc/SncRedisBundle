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
 * RedisSpool5 (Swift Mailer 5.x)
 */
class RedisSpool5 extends RedisSpool
{
    /**
     * {@inheritdoc}
     */
    public function queueMessage(\Swift_Mime_Message $message)
    {
        $this->redis->rpush($this->key, serialize($message));

        return true;
    }
}
