<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Predis\Network;

use Predis\Commands\ICommand;
use Predis\ResponseError;
use Predis\Network\IConnectionSingle;
use Snc\RedisBundle\Logger\RedisLogger;

/**
 * ConnectionWrapper
 */
class ConnectionWrapper implements IConnectionSingle
{
    /**
     * @var IConnectionSingle
     */
    protected $connection;

    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param IConnectionSingle $connection
     */
    public function __construct(IConnectionSingle $connection)
    {
        if ($connection instanceof ConnectionWrapper) {
            $connection = $connection->getConnection();
        }

        $this->connection = $connection;
    }

    /**
     * Returns the underlying connection object
     *
     * @return IConnectionSingle
     */
    public function getConnection()
    {
        return $this->connection;
    }

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
    public function connect()
    {
        return $this->connection->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        return $this->connection->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->connection->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function writeCommand(ICommand $command)
    {
        return $this->connection->writeCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(ICommand $command)
    {
        return $this->connection->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->connection->getResource();
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->connection->getParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function pushInitCommand(ICommand $command)
    {
        return $this->connection->pushInitCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        return $this->connection->read();
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ICommand $command) {
        if (null === $this->logger) {
            return $this->connection->executeCommand($command);
        }

        $startTime = microtime(true);
        $result = $this->connection->executeCommand($command);
        $duration = (microtime(true) - $startTime) * 1000;

        $error = $result instanceof ResponseError ? (string) $result : false;
        $this->logger->logCommand((string) $command, $duration, $this->getParameters()->alias, $error);

        return $result;
    }
}
