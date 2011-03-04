<?php

namespace Snc\RedisBundle\Client\Predis;

use Predis\ConnectionParameters;
use Predis\ICommand;
use Predis\Network\StreamConnection;
use Snc\RedisBundle\Logger\RedisLogger;

/**
 * LoggingConnection
 */
class LoggingConnection extends StreamConnection
{
    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param ConnectionParameters $parameters A ConnectionParameters instance
     * @param RedisLogger $logger A RedisLogger instance
     */
    public function __construct(ConnectionParameters $parameters, RedisLogger $logger = null)
    {
        $this->logger = $logger;
        parent::__construct($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function writeCommand(ICommand $command)
    {
        $time = microtime(true);
        parent::writeCommand($command);
        if (null !== $this->logger) {
            $this->logger->startCommand($this->serializeCommand($command), $time, $this->_params->alias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(ICommand $command)
    {
        $result = parent::readResponse($command);
        if (null !== $this->logger) {
            $this->logger->stopCommand();
        }
        return $result;
    }

    /**
     * Serializes a command for the logger.
     *
     * @param ICommand $command
     * @return string
     */
    protected function serializeCommand(ICommand $command)
    {
        return trim(sprintf('%s %s', $command->getCommandId(), implode(' ', $command->getArguments())));
    }
}
