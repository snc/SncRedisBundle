<?php

namespace Snc\RedisBundle\Client\Predis;

use Snc\RedisBundle\Logger\RedisLogger;

class ConnectionFactory extends \Predis\ConnectionFactory
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
    public function create($parameters)
    {
        $connection = parent::create($parameters);
        if ($parameters->logging) {
            $connection->setLogger($this->logger);
        }
        return $connection;
    }
}
