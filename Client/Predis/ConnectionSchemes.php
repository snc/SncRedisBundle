<?php

namespace Snc\RedisBundle\Client\Predis;

use Snc\RedisBundle\Logger\RedisLogger;

class ConnectionSchemes extends \Predis\ConnectionSchemes
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
    public function setLogger(RedisLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function newConnection($parameters)
    {
        $connection = parent::newConnection($parameters);
        if ($parameters->logging) {
            $connection->setLogger($this->logger);
        }
        return $connection;
    }
}
