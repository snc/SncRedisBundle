<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 * (c) Yassine Khial <yassine.khial@blablacar.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Phpredis;

use Snc\RedisBundle\Logger\RedisLogger;

/**
 * Simple \Redis Proxy with logger:
 * Add log to the RedisLogger object when calling specific command
 */
class Client extends BaseClient
{
    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $alias;

    /**
     * Constructor
     *
     * @param array       $parameters List of parameters (only `alias` key is handled)
     * @param RedisLogger $logger     A RedisLogger instance
     */
    public function __construct(array $parameters = array(), RedisLogger $logger = null)
    {
        $this->logger = $logger;
        $this->alias  = isset($parameters['alias']) ? $parameters['alias'] : '';
    }

    /**
     * {@inheritDoc}
     * Overload somes commands (get, set...) in order to log the result
     */
    public function __call($name, array $arguments)
    {
        $log = true;

        switch (strtolower($name)) {
            case 'connect':
            case 'open':
            case 'pconnect':
            case 'popen':
            case 'close':
            case 'setoption':
            case 'getoption':
            case 'auth':
            case 'select':
                $log = false;
                break;
        }

        $startTime = microtime(true);
        $result    = parent::__call($name, $arguments);
        $duration  = (microtime(true) - $startTime) * 1000;

        if ($log && null !== $this->logger) {
            $this->logger->logCommand($this->getCommandString($name, $arguments), $duration, $this->alias, false);
        }

        return $result;
    }

    /**
     * Returns a string representation of the given command including arguments
     *
     * @param string $command   A command name
     * @param array  $arguments List of command arguments
     *
     * @return string
     */
    private function getCommandString($command, array $arguments)
    {
        $list = array();
        $this->flatten($arguments, $list);

        return trim(strtoupper($command) . ' ' . implode(' ', $list));
    }

    /**
     * Flatten arguments to single dimension array
     *
     * @param array $arguments An array of command arguments
     * @param array $list Holder of results
     */
    private function flatten($arguments, array &$list)
    {
        foreach ($arguments as $key => $item) {
            if (!is_numeric($key)) {
                $list[] = $key;
            }

            if (is_scalar($item)) {
                $list[] = strval($item);
            } elseif (null === $item) {
                $list[] = '<null>';
            } else {
                $this->flatten($item, $list);
            }
        }
    }
}
