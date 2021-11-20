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
 * @deprecated
 */
class RedisSpool6 extends RedisSpool
{
    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function queueMessage(\Swift_Mime_SimpleMessage $message)
    {
        $this->redis->rpush($this->key, serialize($message));

        return true;
    }
}
