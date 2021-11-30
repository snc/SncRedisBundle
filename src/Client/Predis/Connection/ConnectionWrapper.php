<?php

declare(strict_types=1);

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Predis\Connection;

use Closure;
use Predis\Command\CommandInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\ParametersInterface;
use Predis\Response\Error;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Stopwatch\Stopwatch;

use function array_reduce;
use function assert;
use function microtime;
use function strlen;
use function substr;

/**
 * ConnectionWrapper
 */
class ConnectionWrapper implements NodeConnectionInterface
{
    protected NodeConnectionInterface $connection;

    protected ?RedisLogger $logger = null;

    protected ?Stopwatch $stopwatch = null;

    /**
     * Constructor
     */
    public function __construct(NodeConnectionInterface $connection)
    {
        if ($connection instanceof ConnectionWrapper) {
            $connection = $connection->getConnection();
            assert($connection instanceof ConnectionWrapper);
        }

        $this->connection = $connection;
    }

    /**
     * Returns the underlying connection object
     */
    public function getConnection(): NodeConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Sets the logger
     *
     * @param RedisLogger $logger A RedisLogger instance
     */
    public function setLogger(?RedisLogger $logger = null): void
    {
        $this->logger = $logger;
    }

    public function setStopwatch(Stopwatch $stopwatch): void
    {
        $this->stopwatch = $stopwatch;
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

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        return $this->execute($command, function (CommandInterface $command) {
            return $this->connection->writeRequest($command);
        });
    }

    /**
     * @return mixed
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
     * @return mixed
     */
    public function getResource()
    {
        return $this->connection->getResource();
    }

    public function getParameters(): ParametersInterface
    {
        return $this->connection->getParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function addConnectCommand(CommandInterface $command)
    {
        return $this->connection->addConnectCommand($command);
    }

    /**
     * @return mixed
     */
    public function read()
    {
        return $this->connection->read();
    }

    /**
     * @return mixed
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->execute($command, function (CommandInterface $command) {
            return $this->connection->executeCommand($command);
        });
    }

    private function commandToString(CommandInterface $command): string
    {
        return array_reduce(
            $command->getArguments(),
            fn (string $accumulator, string $argument) => $this->toStringArgumentReducer($accumulator, $argument),
            $command->getId()
        );
    }

    /**
     * Helper function used to reduce a list of arguments to a string.
     */
    private function toStringArgumentReducer(string $accumulator, string $argument): string
    {
        if (strlen($argument) > 10240) {
            $argument = substr($argument, 0, 10240) . ' (truncated, complete string is ' . strlen($argument) . ' bytes)';
        }

        return $accumulator . ' ' . $argument;
    }

    /**
     * @param Closure(CommandInterface ): mixed $execute
     *
     * @return mixed
     */
    private function execute(CommandInterface $command, Closure $execute)
    {
        if (!$this->logger) {
            return $execute($command);
        }

        $commandName = $this->commandToString($command);

        if ($this->stopwatch) {
            $event = $this->stopwatch->start($commandName, 'redis');
        }

        $startTime = microtime(true);
        $result    = $execute($command);

        if (isset($event)) {
            $event->stop();
        }

        /** @psalm-suppress NoInterfaceProperties */
        $this->logger->logCommand(
            $this->commandToString($command),
            (microtime(true) - $startTime) * 1000,
            $this->getParameters()->alias,
            $this->isResultTrulyAnError($result) ? (string) $result : false
        );

        return $result;
    }

    /**
     * Certain results from redis are marked as "error", but are meant to be handled by redis client.
     * Unfortunately, in some cases this evaluation happens only after executeCommand in ConnectionWrapper returns.
     * For example, RedisCluster wraps ConnectionWrapper and not opposite, hence ConnectionWrapper must make assumption
     * here about handling of certain errors based on error type and connection parameters in order of not wrongly
     * classifying responses as errors that were already taken care of by client.
     *
     * @param mixed $result
     */
    private function isResultTrulyAnError($result): bool
    {
        if (!$result instanceof Error) {
            return false;
        }

        /** @psalm-suppress NoInterfaceProperties */
        if ($this->connection->getParameters()->cluster) {
            return $result->getErrorType() !== 'MOVED';
        }

        return true;
    }
}
