<?php

namespace Snc\RedisBundle\Client\Predis\Network;

use Predis\Commands\ICommand;
use Predis\Network\IConnectionSingle;
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
