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

use Predis\Distribution\IDistributionStrategy;
use Snc\RedisBundle\Client\Predis\Distribution\RandomDistributionStrategy;

/**
 * Sample cluster class which uses a custom distribution strategy
 */
class PredisCluster extends \Predis\Network\PredisCluster
{
    /**
     * Constructor
     *
     * @param null|\Predis\Distribution\IDistributionStrategy $distributor
     */
    public function __construct(IDistributionStrategy $distributor = null)
    {
        parent::__construct(new RandomDistributionStrategy());
    }
}
