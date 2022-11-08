<?php

namespace Snc\RedisBundle\Logger;

use Symfony\Component\Stopwatch\Stopwatch;

use function implode;
use function is_numeric;
use function is_scalar;
use function microtime;
use function preg_replace;
use function strtoupper;
use function strval;
use function trim;

class RedisCallInterceptor
{
    private RedisLogger $logger;
    private ?Stopwatch $stopwatch;

    public function __construct(RedisLogger $logger, ?Stopwatch $stopwatch = null)
    {
        $this->logger    = $logger;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @param list<mixed> $args
     *
     * @return mixed
     */
    public function __invoke(
        object $instance,
        string $method,
        array $args,
        ?string $connection
    ) {
        $command = $this->getCommandString($method, $args);
        $time    = microtime(true);

        if ($this->stopwatch) {
            $event = $this->stopwatch->start(preg_replace('/[^[:print:]]/', '', $command), 'redis');
        }

        try {
            $return = $instance->$method(...$args);
        } finally {
            $this->logger->logCommand($command, (microtime(true) - $time) * 1000, $connection);
        }

        if (isset($event)) {
            $event->stop();
        }

        return $return;
    }

    /**
     * Returns a string representation of the given command including arguments.
     *
     * @param mixed[] $arguments List of command arguments
     */
    private function getCommandString(string $command, array $arguments): string
    {
        $list = [];
        $this->flatten($arguments, $list);

        return trim(strtoupper($command) . ' ' . implode(' ', $list));
    }

    /**
     * Flatten arguments to single dimension array.
     *
     * @param mixed[] $arguments An array of command arguments
     * @param mixed[] $list      Holder of results
     */
    private function flatten(array $arguments, array &$list): void
    {
        foreach ($arguments as $key => $item) {
            if (!is_numeric($key)) {
                $list[] = $key;
            }

            if (is_scalar($item)) {
                $list[] = strval($item);
            } elseif ($item === null) {
                $list[] = '<null>';
            } else {
                $this->flatten($item, $list);
            }
        }
    }
}
