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

use Predis\Command\CommandInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Response\Error;
use Snc\RedisBundle\Logger\RedisLogger;

/**
 * ConnectionWrapper
 */
class ConnectionWrapper implements NodeConnectionInterface
{
    /**
     * @var NodeConnectionInterface
     */
    protected $connection;

    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param NodeConnectionInterface $connection
     */
    public function __construct(NodeConnectionInterface $connection)
    {
        if ($connection instanceof ConnectionWrapper) {
            /** @var ConnectionWrapper $connection */
            $connection = $connection->getConnection();
        }

        $this->connection = $connection;
    }

    /**
     * Returns the underlying connection object
     *
     * @return NodeConnectionInterface
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
        return $this->connection->disconnect();
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
    public function writeRequest(CommandInterface $command)
    {
        return $this->connection->writeRequest($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
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
    public function pushInitCommand(CommandInterface $command)
    {
        return $this->connection->pushInitCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function addConnectCommand(CommandInterface $command)
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
    public function executeCommand(CommandInterface $command)
    {
        if (null === $this->logger) {
            return $this->connection->executeCommand($command);
        }

        $startTime = microtime(true);
        $result = $this->connection->executeCommand($command);
        $duration = (microtime(true) - $startTime) * 1000;

        $error = $result instanceof Error ? (string) $result : false;
        $this->logger->logCommand($this->commandToString($command), $duration, $this->getParameters()->alias, $error);

        return $result;
    }

    private function commandToString(CommandInterface $command)
    {
        return array_reduce(
            $command->getArguments(),
            array($this, 'toStringArgumentReducer'),
            $command->getId()
        );
    }

    /**
     * Helper function used to reduce a list of arguments to a string.
     *
     * @param  string $accumulator Temporary string.
     * @param  string $argument    Current argument.
     * @return string
     */
    private function toStringArgumentReducer($accumulator, $argument)
    {
        if (strlen($argument) > 32) {
            $argument = substr($argument, 0, 32) . '[...]';
        }

        $accumulator .= " $argument";

        return $accumulator;
    }
}
