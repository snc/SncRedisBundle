<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Predis\Distribution;

use Predis\Distribution\IDistributionStrategy;

/**
 * This distribution strategy will simply return a random connection
 */
class RandomDistributionStrategy implements IDistributionStrategy
{
    /**
     * @var array
     */
    private $_nodes;

    /**
     * @var int
     */
    private $_nodesCount;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_nodes = array();
        $this->_nodesCount = 0;
    }

    /**
     * @param \Predis\Network\IConnectionSingle $node
     * @param int $weight
     * @return void
     */
    public function add($node, $weight = null)
    {
        $this->_nodes[] = $node;
        $this->_nodesCount++;
    }

    /**
     * @param \Predis\Network\IConnectionSingle $node
     */
    public function remove($node)
    {
        $this->_nodes = array_filter($this->_nodes, function($n) use($node)
        {
            return $n !== $node;
        });
        $this->_nodesCount = count($this->_nodes);
    }

    /**
     * @param string $key
     * @return \Predis\Network\IConnectionSingle
     */
    public function get($key)
    {
        if (0 === $this->_nodesCount) {
            throw new \OutOfBoundsException('No connections.');
        }
        return $this->_nodes[array_rand($this->_nodes)];
    }

    /**
     * @param string $value
     * @return bool
     */
    public function generateKey($value)
    {
        return true; // the key is irrelevant for random distribution
    }
}
