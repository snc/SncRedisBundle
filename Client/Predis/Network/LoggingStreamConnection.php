<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Predis\Network;

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
     * Sets the logger
     *
     * @param RedisLogger $logger A RedisLogger instance
     */
    public function setLogger(RedisLogger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ICommand $command) {
        $startTime = microtime(true);
        $result = parent::executeCommand($command);
        $duration = (microtime(true) - $startTime) * 1000;
        if (null !== $this->logger) {
            $error = $result instanceof ResponseError ? (string) $result : false;
            $this->logger->logCommand((string) $command, $duration, $this->_params->alias, $error);
        }
        return $result;
    }
}
