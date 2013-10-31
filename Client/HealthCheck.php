<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Liip\Monitor\Result\CheckResult;
use Liip\Monitor\Check\Check;

/**
 * Health check for the liip monitor bundle.
 *
 * Check is performed by pinging the redis backend clients.
 *
 * @see https://github.com/liip/LiipMonitorBundle
 */
class HealthCheck extends Check
{
    /**
     * @var array
     */
    protected $clients;

    public function __construct()
    {
        $this->clients = array();
    }

    /**
     * @param string $id
     * @param \Redis|\Predis\Client $client
     */
    public function addClient($id, $client)
    {
        $this->clients[$id] = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function check()
    {
        foreach ($this->clients as $id => $client) {

            if ($client->ping() !== '+PONG') {
                return $this->buildResult('Unable to ping redis backend: ' . $id, CheckResult::CRITICAL);
            }
        }

        return $this->buildResult('OK', CheckResult::OK);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'Redis Health Check';
    }
}
