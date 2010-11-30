<?php

namespace Bundle\RedisBundle\DataCollector;

use Bundle\RedisBundle\Logger\RedisLogger;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RedisDataCollector
 */
class RedisDataCollector extends DataCollector
{
    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param RedisLogger $logger
     */
    public function __construct(RedisLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'commands' => null !== $this->logger ? $this->logger->getCommands() : array(),
        );
    }

    /**
     * Returns an array of collected commands.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->data['commands'];
    }

    /**
     * Returns the number of collected commands.
     *
     * @return integer
     */
    public function getCommandCount()
    {
        return count($this->data['commands']);
    }

    /**
     * Returns the execution time of all collected commands in seconds.
     *
     * @return float
     */
    public function getTime()
    {
        $time = 0;
        foreach ($this->data['commands'] as $command) {
            $time += $command['executionMS'];
        }
        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'redis';
    }
}
