<?php

namespace Bundle\RedisBundle\Client\Predis;

use Bundle\RedisBundle\Logger\RedisLogger;

/**
 * LoggingConnection
 */
class LoggingConnection extends \Predis\TcpConnection
{
    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param RedisLogger $logger A RedisLogger instance
     * @param array $options An array of options
     */
    public function __construct(RedisLogger $logger, array $options = array())
    {
        $this->logger = $logger;
        parent::__construct(new \Predis\ConnectionParameters($options));
    }

    /**
     * {@inheritdoc}
     */
    public function writeCommand(\Predis\ICommand $command)
    {
        $time = microtime(true);
        parent::writeCommand($command);
        if (null !== $this->logger) {
            $this->logger->startCommand($this->serializeCommand($command), $time);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(\Predis\ICommand $command)
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
     * @param \Predis\ICommand $command
     * @return string
     */
    protected function serializeCommand(\Predis\ICommand $command)
    {
        return trim(sprintf('%s %s', $command->getCommandId(), implode(' ', $command->getArguments())));
    }
}
