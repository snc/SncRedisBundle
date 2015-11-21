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

use Predis\Cluster\Distributor\DistributorInterface;
use Snc\RedisBundle\Client\Predis\Cluster\Distribution\RandomDistributionStrategy;

/**
 * Sample cluster class which uses a custom distribution strategy
 */
class PredisCluster extends \Predis\Connection\Aggregate\PredisCluster
{
    /**
     * Constructor
     *
     * @param null|\Predis\Cluster\Distributor\DistributorInterface $distributor
     */
    public function __construct(DistributorInterface $distributor = null)
    {
        parent::__construct(new RandomDistributionStrategy());
    }
}
