<?php

namespace Snc\RedisBundle\Client\Predis;

use Predis\ConnectionParameters;
use Predis\Commands\ICommand;
use Predis\ResponseError;
use Predis\Network\StreamConnection;
use Snc\RedisBundle\Logger\RedisLogger;

/**
 * LoggingStreamConnection
 */
class LoggingStreamConnection extends StreamConnection
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
            $this->logger->startCommand((string)$command, $time, $this->_params->alias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(ICommand $command)
    {
        $result = parent::readResponse($command);
        if (null !== $this->logger) {
            $error = $result instanceof ResponseError ? (string)$result : false;
            $this->logger->stopCommand($error);
        }
        return $result;
    }
}
