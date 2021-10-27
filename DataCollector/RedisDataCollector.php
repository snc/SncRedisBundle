<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DataCollector;

use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * RedisDataCollector
 */
class RedisDataCollector extends DataCollector
{
    use DataCollectorSymfonyCompatibilityTrait;

    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param RedisLogger $logger
     */
    public function __construct(RedisLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = array();
    }

    /**
     * Returns an array of collected commands.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->data['commands'];
    }

    /**
     * Returns the number of collected commands.
     *
     * @return integer
     */
    public function getCommandCount()
    {
        return count($this->data['commands']);
    }

    /**
     * Returns the number of failed commands.
     *
     * @return integer
     */
    public function getErroredCommandsCount()
    {
        return count(array_filter($this->data['commands'], function ($command) {
            return $command['error'] !== false;
        }));
    }

    /**
     * Returns the execution time of all collected commands in seconds.
     *
     * @return float
     */
    public function getTime()
    {
        $time = 0;
        foreach ($this->data['commands'] as $command) {
            $time += $command['executionMS'];
        }

        return $time;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'redis';
    }
}
