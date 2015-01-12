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

use Symfony\Component\Console\Input\InputArgument;

/**
 * Command for delete redis keys with specified prefix from the command line
 *
 * @author Tomasz Cyrankowski <tomasz.cyrankowski@gmail.com>
 */
class RedisDelprefixCommand extends RedisBaseCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('redis:delprefix')
             ->setDescription('Deletes all keys with specified prefix ex. dcmc, dcqc, dcrc, session')
             ->addArgument('prefix', InputArgument::REQUIRED, 'Prefix name', null);
    }

    /**
     * {@inheritDoc}
     */
    protected function executeRedisCommand()
    {
        $keys = $this->getKeysWithPrefix();

        if ($this->proceedingAllowed(sprintf("Are you sure you wish to delete %d keys with prefix '%s' ? (y/n)", count($keys), $this->input->getArgument('prefix')))) {
            $this->delPrefix($keys);
        } else {
            $this->output->writeln('<error>Deleting keys cancelled</error>');
        }
    }

    /**
     * Get all keys with prefix
     *
     * @return array $keys
     */
    private function getKeysWithPrefix()
    {

        return $this->redisClient->keys($this->input->getArgument('prefix') . '*');
    }

    /**
     * Deleting prefix
     *
     * @param array $keys
     */
    private function delPrefix(array $keys)
    {
        foreach ($keys as $key) {
            $this->redisClient->del($key);
        }

        $this->output->writeln('<info>Deleted all redis keys with prefix ' . $this->input->getArgument('prefix') . '</info>');
    }
}
