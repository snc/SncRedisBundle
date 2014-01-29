<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Phpredis;

use Redis;
use Snc\RedisBundle\Logger\RedisLogger;

/**
 * phpredis client wrapper
 */
class Client
{
    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $alias;

    /**
     * Constructor
     *
     * @param array       $parameters An array of parameters
     * @param RedisLogger $logger     A RedisLogger instance
     */
    public function __construct(array $parameters = array(), RedisLogger $logger = null)
    {
        $this->logger = $logger;
        $this->alias = $parameters['alias'];
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (null !== $this->redis) {
            $this->redis->close();
        }
    }

    /**
     * Sets the redis instance
     *
     * @param \Redis $redis
     */
    public function setRedis(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Proxy function to enable logging
     *
     * @param string $name      A command name
     * @param array  $arguments An array of command arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        $log = true;

        switch (strtolower($name)) {
            case 'connect':
            case 'open':
            case 'pconnect':
            case 'popen':
            case 'close':
            case 'setoption':
            case 'getoption':
            case 'auth':
            case 'select':
                $log = false;
                break;
        }

        $startTime = microtime(true);
        $result = call_user_func_array(array($this->redis, $name), $arguments);
        $duration = (microtime(true) - $startTime) * 1000;

        if ($log && null !== $this->logger) {
            $this->logger->logCommand($this->getCommandString($name, $arguments), $duration, $this->alias, false);
        }

        return $result;
    }

    /**
     * Returns a string representation of the given command including arguments
     *
     * @param string $command   A command name
     * @param array  $arguments An array of command arguments
     *
     * @return string
     */
    private function getCommandString($command, array $arguments)
    {
        $list = array();
        $this->flatten($arguments, $list);

        return trim(strtoupper($command) . ' ' . implode(' ', $list));
    }

    /**
     * Flatten arguments to single dimension array
     *
     * @param array $arguments An array of command arguments
     * @param array $list Holder of results
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
}
