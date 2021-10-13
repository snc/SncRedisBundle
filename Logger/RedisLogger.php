<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Logger;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * RedisLogger
 */
class RedisLogger
{
    protected $logger;
    protected $nbCommands = 0;
    protected $commands = array();
    private $bufferSize = 200;

    /**
     * Constructor.
     *
     * @param PsrLoggerInterface $logger A LoggerInterface instance
     */
    public function __construct($logger = null, int $bufferSize = 200)
    {
        if (!$logger instanceof PsrLoggerInterface && null !== $logger) {
            throw new \InvalidArgumentException(sprintf('RedisLogger needs a PSR-3 LoggerInterface, "%s" was injected instead.', is_object($logger) ? get_class($logger) : gettype($logger)));
        }

        $this->logger = $logger;
        $this->bufferSize = $bufferSize;
    }

    /**
     * Logs a command
     *
     * @param string      $command    Redis command
     * @param float       $duration   Duration in milliseconds
     * @param string      $connection Connection alias
     * @param bool|string $error      Error message or false if command was successful
     */
    public function logCommand($command, $duration, $connection, $error = false)
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
     *
     * @return integer
     */
    public function getNbCommands()
    {
        return $this->nbCommands;
    }

    /**
     * Returns an array of the logged commands.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
