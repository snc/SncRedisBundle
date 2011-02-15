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
     * Logs a command start.
     *
     * @param string $command Redis command
     * @param float $time Start time
     */
    public function startCommand($command, $time = null, $connection = null)
    {
        ++$this->nbCommands;

        if (null !== $this->logger) {
            $this->commands[] = array('cmd' => $command, 'executionMS' => 0, 'conn' => $connection);
            $this->logger->info(static::LOG_PREFIX . $command);
            $this->start = $time ? : microtime(true);
        }
    }

    /**
     * Logs a command stop.
     */
    public function stopCommand()
    {
        if (null !== $this->logger) {
            $this->commands[(count($this->commands) - 1)]['executionMS'] = (microtime(true) - $this->start) * 1000;
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
