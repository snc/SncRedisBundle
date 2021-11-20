<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Tests\Extra;

use Predis\Client;
use Snc\RedisBundle\Extra\RateLimit;
use PHPUnit\Framework\TestCase;

/**
 * RateLimitTest.
 *
 * @author Pierre Boudelle <pierre.boudelle@gmail.com>
 * @group legacy
 */
class RateLimitTest extends TestCase
{
    protected $_redis;

    protected $_uniqid;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (class_exists(Client::class)) {
            $this->_redis = new \Predis\Client('tcp://127.0.0.1:6379', ['parameters' => ['password' => 'sncredis']]);
            $this->_redis->ping();
        } else {
            $this->markTestSkipped(sprintf('The %s requires the predis library.', __CLASS__));
        }

        // Use a unique namespace
        $this->_uniqid = uniqid(__METHOD__, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->_redis);
    }

    public function testRateLimit()
    {
        // Set the bucket interval to 1 second
        $rateLimit = new RateLimit($this->_redis, 'testRateLimit', 600, 1);
        $limit = 1;

        // Increment twice the counter
        $rateLimit->increment($this->_uniqid);
        $count = $rateLimit->incrementAndCount($this->_uniqid, $limit);
        $this->assertEquals(2, $count);

        // Wait $limit + 1 seconds to make sure it doesn't get into the same interval, then assert that the counter has reset
        sleep($limit + 1);

        $count = $rateLimit->incrementAndCount($this->_uniqid, $limit);
        $this->assertEquals(1, $count);
    }

    public function testRateLimitReset()
    {
        // Set the bucket interval to 1 second
        $rateLimit = new RateLimit($this->_redis, 'testRateLimitReset', 600, 1);
        $limit = 2;

        // Increment once the counter
        $count = $rateLimit->incrementAndCount($this->_uniqid, $limit);
        $this->assertEquals(1, $count);

        // Reset and assert
        $rateLimit->reset($this->_uniqid);

        $count = $rateLimit->incrementAndCount($this->_uniqid, $limit);
        $this->assertEquals(1, $count);
    }
}
