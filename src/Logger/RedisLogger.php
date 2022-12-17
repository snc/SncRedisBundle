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
use Symfony\Contracts\Service\ResetInterface;

use function array_shift;
use function count;

class RedisLogger implements ResetInterface
{
    protected ?LoggerInterface $logger;
    protected int $nbCommands = 0;
    /** @var list<array{cmd: string, executionMS: float, conn: ?string, error: string|false}> */
    protected array $commands = [];
    private int $bufferSize   = 200;

    public function __construct(?LoggerInterface $logger = null, int $bufferSize = 200)
    {
        $this->logger     = $logger;
        $this->bufferSize = $bufferSize;
    }

    /** @param false|string $error Error message or false if command was successful */
    public function logCommand(string $command, float $duration, ?string $connection, $error = false): void
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

    public function getNbCommands(): int
    {
        return $this->nbCommands;
    }

    /** @return list<array{cmd: string, executionMS: float, conn: string, error: string|false}> */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function reset(): void
    {
        $this->commands   = [];
        $this->nbCommands = 0;
    }
}
