<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Predis\Connection;

use Predis\Cluster\Distribution\DistributionStrategyInterface;
use Snc\RedisBundle\Client\Predis\Cluster\Distribution\RandomDistributionStrategy;

/**
 * Sample cluster class which uses a custom distribution strategy
 */
class PredisCluster extends \Predis\Connection\PredisCluster
{
    /**
     * Constructor
     *
     * @param null|\Predis\Cluster\Distribution\DistributionStrategyInterface $distributor
     */
    public function __construct(DistributionStrategyInterface $distributor = null)
    {
        parent::__construct(new RandomDistributionStrategy());
    }
}
