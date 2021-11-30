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

namespace Snc\RedisBundle\Logger;

use Psr\Log\LoggerInterface;

use function array_shift;
use function count;

/**
 * RedisLogger
 */
class RedisLogger
{
    protected ?LoggerInterface $logger;
    protected int $nbCommands = 0;
    /** @var array{cmd: string, executionMS: float, conn: string, error: string|false} */
    protected array $commands = [];
    private int $bufferSize   = 200;

    public function __construct(?LoggerInterface $logger = null, int $bufferSize = 200)
    {
        $this->logger     = $logger;
        $this->bufferSize = $bufferSize;
    }

    /**
     * Logs a command
     *
     * @param string       $command    Redis command
     * @param float        $duration   Duration in milliseconds
     * @param string       $connection Connection alias
     * @param false|string $error      Error message or false if command was successful
     */
    public function logCommand(string $command, float $duration, string $connection, $error = false): void
    {
        ++$this->nbCommands;

        if (!$this->logger) {
            return;
        }

        if (count($this->commands) > $this->bufferSize) {
            // Prevents memory leak
            array_shift($this->commands);
        }

        $this->commands[] = ['cmd' => $command, 'executionMS' => $duration, 'conn' => $connection, 'error' => $error];

        if ($error) {
            $this->logger->error('Command "' . $command . '" failed (' . $error . ')');

            return;
        }

        $this->logger->debug('Executing command "' . $command . '"');
    }

    /**
     * Returns the number of logged commands.
     */
    public function getNbCommands(): int
    {
        return $this->nbCommands;
    }

    /**
     * Returns an array of the logged commands.
     *
     * @return array{cmd: string, executionMS: float, conn: string, error: string|false}
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}
