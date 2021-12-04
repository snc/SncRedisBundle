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

use Predis\Connection\Factory;
use Predis\Connection\NodeConnectionInterface;
use Snc\RedisBundle\Logger\RedisLogger;
use Symfony\Component\Stopwatch\Stopwatch;

class ConnectionFactory extends Factory
{
    /** @var class-string<ConnectionWrapper> */
    protected ?string $wrapper = null;

    protected RedisLogger $logger;

    protected ?Stopwatch $stopwatch = null;

    public function setLogger(?RedisLogger $logger = null): void
    {
        $this->logger = $logger;
    }

    public function setStopwatch(?Stopwatch $stopwatch): void
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * Sets the connection wrapper class used to wrap an actual
     * connection object and enable logging.
     *
     * @param class-string<ConnectionWrapper> $class Fully qualified name of the connection wrapper class.
     */
    public function setConnectionWrapperClass(string $class): void
    {
        $this->wrapper = $class;
    }

    /** @param mixed $parameters */
    public function create($parameters): NodeConnectionInterface
    {
        if (isset($parameters->parameters)) {
            $this->setDefaultParameters($parameters->parameters);
        }

        $connection = parent::create($parameters);

        if ($this->wrapper === null) {
            return $connection;
        }

        $wrapper    = $this->wrapper;
        $connection = new $wrapper($connection);
        $connection->setLogger($this->logger);

        if ($this->stopwatch) {
            $connection->setStopwatch($this->stopwatch);
        }

        return $connection;
    }
}
