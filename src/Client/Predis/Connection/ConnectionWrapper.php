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
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\ParametersInterface;
use Predis\Response\Error;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Stopwatch\Stopwatch;

use function array_reduce;
use function assert;
use function is_string;
use function microtime;
use function preg_replace;
use function strlen;
use function substr;
use function var_export;

class ConnectionWrapper implements NodeConnectionInterface
{
    protected NodeConnectionInterface $connection;

    protected ?RedisLogger $logger = null;

    protected ?Stopwatch $stopwatch = null;

    public function __construct(NodeConnectionInterface $connection)
    {
        if ($connection instanceof ConnectionWrapper) {
            $connection = $connection->getConnection();
            assert($connection instanceof ConnectionWrapper);
        }

        $this->connection = $connection;
    }

    public function getConnection(): NodeConnectionInterface
    {
        return $this->connection;
    }

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

    public function disconnect(): void
    {
        $this->connection->disconnect();
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function writeRequest(CommandInterface $command): void
    {
        $this->execute($command, fn (CommandInterface $command) => $this->connection->writeRequest($command));
    }

    /** @return mixed */
    public function readResponse(CommandInterface $command)
    {
        return $this->connection->readResponse($command);
    }

    public function __toString(): string
    {
        return (string) $this->connection;
    }

    /** @return resource */
    public function getResource()
    {
        return $this->connection->getResource();
    }

    public function getParameters(): ParametersInterface
    {
        return $this->connection->getParameters();
    }

    public function addConnectCommand(CommandInterface $command): void
    {
        $this->connection->addConnectCommand($command);
    }

    /** @return mixed */
    public function read()
    {
        return $this->connection->read();
    }

    /** @return mixed */
    public function executeCommand(CommandInterface $command)
    {
        return $this->execute($command, fn (CommandInterface $command) => $this->connection->executeCommand($command));
    }

    private function commandToString(CommandInterface $command): string
    {
        return array_reduce(
            $command->getArguments(),
            fn (string $accumulator, $argument) => $this->toStringArgumentReducer($accumulator, $argument),
            $command->getId(),
        );
    }

    /** @param mixed $argument */
    private function toStringArgumentReducer(string $accumulator, $argument): string
    {
        if (is_string($argument) && strlen($argument) > 10240) {
            $argument = substr($argument, 0, 10240) . ' (truncated, complete string is ' . strlen($argument) . ' bytes)';
        }

        return $accumulator . ' ' . var_export($argument, true);
    }

    /**
     * @param Closure(CommandInterface): mixed $execute
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
            $event = $this->stopwatch->start(preg_replace('/[^[:print:]]/', '', $commandName), 'redis');
        }

        $startTime = microtime(true);
        try {
            $result = $execute($command);
        } catch (ConnectionException $exception) {
            throw new ConnectionException($this, $exception->getMessage(), $exception->getCode(), $exception);
        }

        if (isset($event)) {
            $event->stop();
        }

        /** @psalm-suppress NoInterfaceProperties */
        $this->logger->logCommand(
            $this->commandToString($command),
            (microtime(true) - $startTime) * 1000,
            $this->getParameters()->alias,
            $result instanceof Error ? $result->getMessage() : false,
        );

        return $result;
    }
}
