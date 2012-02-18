<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;

/**
 * A processing handler for Monolog
 */
class RedisHandler extends AbstractProcessingHandler
{
    /**
     * @var array
     */
    protected $buffer = array();

    /**
     * @var string
     */
    protected $key;

    /**
     * @var \Predis\Client
     */
    protected $redis;

    /**
     * @param \Predis\Client $redis
     */
    public function setRedis(\Predis\Client $redis)
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
    public function close()
    {
        $key =& $this->key;
        $buffer =& $this->buffer;
        $this->redis->multiExec(function($multi) use ($key, $buffer) {
            foreach ($buffer as $record) {
                $multi->rpush($key, $record);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $this->buffer[] = (string) $record['formatted'];
    }
}
