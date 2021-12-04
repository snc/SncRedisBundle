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
use function array_reduce;
use function count;

class RedisDataCollector extends DataCollector
{
    protected RedisLogger $logger;

    public function __construct(RedisLogger $logger)
    {
        $this->logger = $logger;
    }

    public function reset(): void
    {
        $this->data = [];
    }

    /** @return array{cmd: string, executionMS: float, conn: string, error: string|false} */
    public function getCommands(): array
    {
        return $this->data['commands'];
    }

    public function getCommandCount(): int
    {
        return count($this->data['commands']);
    }

    public function getErroredCommandsCount(): int
    {
        return count(array_filter($this->data['commands'], static fn (array $command) => $command['error'] !== false));
    }

    public function getTime(): float
    {
        return array_reduce($this->data['commands'], static fn (float $carry, array $command) => $carry + $command['executionMS'], 0);
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = ['commands' => $this->logger->getCommands()];
    }

    public function getName(): string
    {
        return 'redis';
    }
}
