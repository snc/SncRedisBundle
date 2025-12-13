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

use Override;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Throwable;

use function array_filter;
use function array_reduce;
use function count;

final class RedisDataCollector extends DataCollector implements LateDataCollectorInterface
{
    protected RedisLogger $logger;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(RedisLogger $logger)
    {
        $this->logger = $logger;
    }

    #[Override]
    public function reset(): void
    {
        $this->data = [];
    }

    /** @return array{cmd: string, executionMS: float, conn: string, error: string|false} */
    public function getCommands(): array
    {
        return $this->data['commands'];
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getCommandCount(): int
    {
        return count($this->data['commands']);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getErroredCommandsCount(): int
    {
        return count(array_filter($this->data['commands'], static fn (array $command) => $command['error'] !== false));
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getTime(): float
    {
        return array_reduce($this->data['commands'], static fn (float $carry, array $command): float => $carry + $command['executionMS'], 0);
    }

    #[Override]
    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = ['commands' => $this->logger->getCommands()];
    }

    #[Override]
    public function getName(): string
    {
        return 'redis';
    }

    #[Override]
    public function lateCollect(): void
    {
        $this->data = ['commands' => $this->logger->getCommands()];
    }
}
