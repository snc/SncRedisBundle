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

class RedisFlushAllCommand extends RedisBaseCommand
{
    /** @inheritdoc  */
    protected static $defaultName = 'redis:flushall';

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Flushes the redis database using the redis flushall command');
    }

    protected function executeRedisCommand(): int
    {
        if (!$this->proceedingAllowed()) {
            $this->output->writeln('<error>Flushing cancelled</error>');

            return 1;
        }

        $this->flushAll();

        return 0;
    }

    private function flushAll(): void
    {
        if ($this->redisClient instanceof RedisCluster) {
            throw new RuntimeException('\RedisCluster support is not yet implemented for this command');
        }

        // flushall in all nodes of cluster
        foreach (is_iterable($this->redisClient) ? $this->redisClient : [$this->redisClient] as $nodeClient) {
            $nodeClient->flushall();
        }

        $this->output->writeln('<info>All redis databases flushed</info>');
    }
}
