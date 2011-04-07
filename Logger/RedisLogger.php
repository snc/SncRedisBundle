<?php

namespace Snc\RedisBundle\Logger;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * RedisLogger
 */
class RedisLogger
{
    const LOG_PREFIX = 'Redis command: ';

    protected $logger;
    protected $nbCommands = 0;
    protected $commands = array();
    protected $start;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Logs a command
     *
     * @param string $command Redis command
     * @param float $duration Duration in milliseconds
     * @param string $connection Connection alias
     * @param string $error Error message or false if command was successful
     * @return void
     */
    public function logCommand($command, $duration, $connection, $error = false)
    {
        ++$this->nbCommands;

        if (null !== $this->logger) {
            $this->commands[] = array('cmd' => $command, 'executionMS' => $duration, 'conn' => $connection, 'error' => $error);
            if ($error) {
                $this->logger->err(static::LOG_PREFIX . $command . ' (' . $error . ')');
            } else {
                $this->logger->info(static::LOG_PREFIX . $command);
            }
        }
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
