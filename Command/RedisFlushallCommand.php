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
 * Symfony command to execute redis flushall
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
class RedisFlushallCommand extends RedisBaseCommand
{
    protected static $defaultName = 'redis:flushall';

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName(self::$defaultName)
            ->setDescription('Flushes the redis database using the redis flushall command');
    }

    /**
     * {@inheritDoc}
     */
    protected function executeRedisCommand()
    {
        if ($this->proceedingAllowed()) {
            $this->flushAll();
        } else {
            $this->output->writeln('<error>Flushing cancelled</error>');
        }
    }

    /**
     * Flushing all redis databases
     */
    private function flushAll()
    {
        if (!($this->redisClient instanceof \IteratorAggregate) || // BC for Predis 1.0
            // bug fix https://github.com/nrk/predis/issues/552
            !($this->redisClient->getConnection() instanceof \Traversable)
        ) {
            $this->redisClient->flushall();
        } else {
            // flushall in all nodes of cluster
            foreach ($this->redisClient as $nodeClient) {
                $nodeClient->flushall();
            }
        }

        $this->output->writeln('<info>All redis databases flushed</info>');
    }
}
