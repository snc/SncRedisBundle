<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Extra;

use Predis\Client;
use Predis\Transaction\MultiExecContext;

/**
 * This class is a PHP port of the RateLimit structure from the high-level
 * Redis library `Redback` for Node.JS
 *
 * @see https://github.com/chriso/redback/blob/master/lib/advanced_structures/RateLimit.js
 * @see http://chris6f.com/rate-limiting-with-redis
 *
 * Usage:
 *
 *     $redisClient = new \Predis\Client();
 *     $rateLimit = new \Snc\RedisBundle\Extra\RateLimit($redisClient, 'heavyActionName');
 *     $count = $rateLimit->incrementAndCount($_SERVER['REMOTE_ADDR'], 60);
 *     if (60 < $count) {
 *         // more than 60 requests within the last 60 seconds...
 *     }
 *
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 */
class RateLimit
{
    /**
     * @var \Predis\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var int
     */
    protected $bucketSpan;

    /**
     * @var int
     */
    protected $bucketInterval;

    /**
     * @var int
     */
    protected $bucketCount;

    /**
     * @var int
     */
    protected $subjectExpiry;

    /**
     * Constructor
     *
     * @param \Predis\Client $client         A \Predis\Client instance
     * @param string         $key            Key
     * @param int            $bucketSpan     Bucket span
     * @param int            $bucketInterval Bucket interval
     * @param int            $subjectExpiry  Subject expiry
     */
    public function __construct(Client $client, $key, $bucketSpan = 600, $bucketInterval = 5, $subjectExpiry = 1200)
    {
        $this->client = $client;
        $this->key = (string) $key;
        $this->bucketSpan = (int) $bucketSpan;
        $this->bucketInterval = (int) $bucketInterval;
        $this->bucketCount = (int) round($this->bucketSpan / $this->bucketInterval);
        $this->subjectExpiry = (int) $subjectExpiry;
    }

    /**
     * Increment the count for the specified subject.
     *
     * @param string $subject A unique identifier, for example a session id or an IP
     */
    public function increment($subject)
    {
        $bucket = $this->getBucket();
        $subject = $this->key . ':' . $subject;
        $multi = $this->client->multiExec();
        $this->addMultiExecIncrement($multi, $subject, $bucket);
        $multi->exec();
    }

    /**
     * Count the number of times the subject has performed an action in the last `$interval` seconds.
     *
     * @param string $subject  A unique identifier, for example a session id or an IP
     * @param int    $interval Interval in seconds
     *
     * @return int
     */
    public function count($subject, $interval)
    {
        $bucket = $this->getBucket();
        $subject = $this->key . ':' . $subject;
        $count = (int) floor($interval / $this->bucketInterval);
        $multi = $this->client->multiExec();
        $this->addMultiExecCount($multi, $subject, $bucket, $count);

        return array_sum($multi->exec());
    }

    /**
     * Calls the increment() and count() function using a single MULTI/EXEC block.
     *
     * @param string $subject  A unique identifier, for example a session id or an IP
     * @param int    $interval Interval in seconds
     *
     * @return int
     */
    public function incrementAndCount($subject, $interval)
    {
        $bucket = $this->getBucket();
        $subject = $this->key . ':' . $subject;
        $count = (int) floor($interval / $this->bucketInterval);
        $multi = $this->client->multiExec();
        $this->addMultiExecIncrement($multi, $subject, $bucket);
        $this->addMultiExecCount($multi, $subject, $bucket, $count);

        return array_sum(array_slice($multi->exec(), 4));
    }

    /**
     * Resets the counter for the specified subject.
     *
     * @param string $subject A unique identifier, for example a session id or an IP
     *
     * @return bool
     */
    public function reset($subject)
    {
        $subject = $this->key . ':' . $subject;

        return (bool) $this->client->del($subject);
    }

    /**
     * Get the bucket associated with the current time.
     *
     * @param int $time (optional) - default is the current time (seconds since epoch)
     *
     * @return int bucket
     */
    private function getBucket($time = null)
    {
        $time = $time ? : time();

        return (int) floor(($time % $this->bucketSpan) / $this->bucketInterval);
    }

    /**
     * Adds the commands needed for the increment function
     *
     * @param MultiExecContext $multi   A MultiExecContext instance
     * @param string           $subject A unique identifier, for example a session id or an IP
     * @param int              $bucket  Bucket
     */
    private function addMultiExecIncrement(MultiExecContext $multi, $subject, $bucket)
    {
        // Increment the current bucket
        $multi->hincrby($subject, $bucket, 1);
        // Clear the buckets ahead
        $multi->hdel($subject, ($bucket + 1) % $this->bucketCount)->hdel($subject, ($bucket + 2) % $this->bucketCount);
        // Renew the key TTL
        $multi->expire($subject, $this->subjectExpiry);
    }

    /**
     * Adds the commands needed for the count function
     *
     * @param MultiExecContext $multi   A MultiExecContext instance
     * @param string           $subject A unique identifier, for example a session id or an IP
     * @param int              $bucket  Bucket
     * @param int              $count   Count
     */
    private function addMultiExecCount(MultiExecContext $multi, $subject, $bucket, $count)
    {
        // Get the counts from the previous `$count` buckets
        $multi->hget($subject, $bucket);
        while ($count--) {
            $multi->hget($subject, (--$bucket + $this->bucketCount) % $this->bucketCount);
        }
    }
}
