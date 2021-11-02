<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Predis\Connection;

use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Factory;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * ConnectionFactory
 */
class ConnectionFactory extends Factory
{
    /**
     * @var ConnectionWrapper
     */
    protected $wrapper;

    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * @var Stopwatch|null
     */
    protected $stopwatch;

    /**
     * Sets the logger
     *
     * @param RedisLogger $logger A RedisLogger instance
     */
    public function setLogger(RedisLogger $logger = null)
    {
        $this->logger = $logger;
    }

    public function setStopwatch(?Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * Sets the connection wrapper class used to wrap an actual
     * connection object and enable logging.
     *
     * @param string $class Fully qualified name of the connection wrapper class.
     */
    public function setConnectionWrapperClass($class)
    {
        $this->wrapper = $class;
    }

    /**
     * {@inheritdoc}
     *
     * @return NodeConnectionInterface
     */
    public function create($parameters)
    {
        if (isset($parameters->parameters)) {
            $this->setDefaultParameters($parameters->parameters);
        }
        /** @var ConnectionWrapper $connection */
        $connection = parent::create($parameters);

        if (null === $this->wrapper) {
            return $connection;
        }

        $wrapper = $this->wrapper;
        $connection = new $wrapper($connection);
        $connection->setLogger($this->logger);

        if ($this->stopwatch) {
            $connection->setStopwatch($this->stopwatch);
        }

        return $connection;
    }
}
