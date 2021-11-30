<?php

declare(strict_types=1);

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Command;

use RedisCluster;
use RuntimeException;

use function is_iterable;

class RedisFlushDbCommand extends RedisBaseCommand
{
    /** @inheritdoc  */
    protected static $defaultName = 'redis:flushdb';

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Flushes the redis database using the redis flushdb command');
    }

    protected function executeRedisCommand(): int
    {
        if (!$this->proceedingAllowed()) {
            $this->output->writeln('<error>Flushing cancelled</error>');

            return 1;
        }

        $this->flushDbForClient();

        return 0;
    }

    /**
     * Getting the client from cmd option and flush's the db
     */
    private function flushDbForClient(): void
    {
        if ($this->redisClient instanceof RedisCluster) {
            throw new RuntimeException('\RedisCluster support is not yet implemented for this command');
        }

        // flushdb in all nodes of cluster
        foreach (is_iterable($this->redisClient) ? $this->redisClient : [$this->redisClient] as $nodeClient) {
            $nodeClient->flushdb();
        }

        $this->output->writeln('<info>redis database flushed</info>');
    }
}
