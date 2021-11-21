<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Command;

/**
 * Symfony command to execute redis flushdb
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
class RedisFlushdbCommand extends RedisBaseCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('redis:flushdb')
            ->setDescription('Flushes the redis database using the redis flushdb command');
    }

    /**
     * {@inheritDoc}
     */
    protected function executeRedisCommand()
    {
        if ($this->proceedingAllowed()) {
            $this->flushDbForClient();
        } else {
            $this->output->writeln('<error>Flushing cancelled</error>');

            return 1;
        }

        return 0;
    }

    /**
     * Getting the client from cmd option and flush's the db
     */
    private function flushDbForClient()
    {
        if ($this->redisClient instanceof \RedisCluster) {
            throw new \RuntimeException('\RedisCluster support is not yet implemented for this command');
        }

        // flushdb in all nodes of cluster
        foreach (is_iterable($this->redisClient) ? $this->redisClient : [$this->redisClient] as $nodeClient) {
            $nodeClient->flushdb();
        }

        $this->output->writeln('<info>redis database flushed</info>');
    }
}
