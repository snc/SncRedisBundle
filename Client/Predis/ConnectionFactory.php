<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Predis;

use Snc\RedisBundle\Logger\RedisLogger;

class ConnectionFactory extends \Predis\ConnectionFactory
{
    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * Sets the logger
     *
     * @param RedisLogger $logger A RedisLogger instance
     */
    public function setLogger(RedisLogger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function create($parameters)
    {
        $connection = parent::create($parameters);
        if ($parameters->logging) {
            $connection->setLogger($this->logger);
        }
        return $connection;
    }
}
