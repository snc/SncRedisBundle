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

use Liip\Monitor\Result\CheckResult;
use Liip\Monitor\Check\Check;

/**
 * Health check for the liip monitor bundle
 *
 * Check is performed by pinging the redis backend.
 *
 * @see https://github.com/liip/LiipMonitorBundle
 */
class HealthCheck extends Check
{
    protected $client;

    /**
     * @param Snc\RedisBundle\Client\Phpredis\Client | Snc\RedisBundle\Client\Predis\Client $client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function check()
    {
        if ($this->client->ping() !== '+PONG') {
            return $this->buildResult('Unable to ping redis backend.', CheckResult::CRITICAL);
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
