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

namespace Snc\RedisBundle\DataCollector;

use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

use function array_filter;
use function count;

/**
 * RedisDataCollector
 */
class RedisDataCollector extends DataCollector
{
    protected RedisLogger $logger;

    public function __construct(RedisLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }

    /**
     * Returns an array of collected commands.
     *
     * @return array{cmd: string, executionMS: float, conn: string, error: string|false}
     */
    public function getCommands(): array
    {
        return $this->data['commands'];
    }

    /**
     * Returns the number of collected commands.
     */
    public function getCommandCount(): int
    {
        return count($this->data['commands']);
    }

    /**
     * Returns the number of failed commands.
     */
    public function getErroredCommandsCount(): int
    {
        return count(array_filter($this->data['commands'], static function ($command) {
            return $command['error'] !== false;
        }));
    }

    /**
     * Returns the execution time of all collected commands in seconds.
     */
    public function getTime(): float
    {
        $time = 0;
        foreach ($this->data['commands'] as $command) {
            $time += $command['executionMS'];
        }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, ?Throwable $exception = null)
    {
        $this->data = ['commands' => $this->logger->getCommands()];
    }

    public function getName(): string
    {
        return 'redis';
    }
}
