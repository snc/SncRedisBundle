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

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('redis:flushall')
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
        $this->redisClient->flushall();

        $this->output->writeln('<info>All redis databases flushed</info>');
    }

}