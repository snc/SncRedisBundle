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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Symfony command to execute redis flushdb
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
class RedisFlushdbCommand extends ContainerAwareCommand
{

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('redis:flushdb')
            ->setDescription('Flushes the redis database using the redis flushdb command')
            ->addOption('client', null, InputOption::VALUE_REQUIRED, 'The name of the client as specified in the config', 'default');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        if ($this->proceedingAllowed()) {
            $this->flushDbForClient();
        } else {
            $this->output->writeln('<error>Flushing cancelled</error>');
        }
    }

    /**
     * Checks if either the no-interaction option was chosen or asks the user to proceed
     *
     * @return boolean true if either no-interaction was chosen or the user wants to proceed
     */
    private function proceedingAllowed()
    {
        if ($this->input->getOption('no-interaction')) {
            return true;
        }

        return $confirmation = $this->getHelper('dialog')->askConfirmation($this->output, '<question>Are you sure you wish to flush the database? (y/n)</question>', false);
    }

    /**
     * Getting the client from cmd option and flush's the db
     */
    private function flushDbForClient()
    {
        $client = $this->input->getOption('client');

        try {
            $redis = $this->getContainer()->get('snc_redis.' . $client);
            $redis->flushdb();

            $this->output->writeln('<info>redis database for client ' . $client . ' flushed</info>');
        } catch (ServiceNotFoundException $e) {
            $this->output->writeln('<error>The client ' . $client . ' is not defined</error>');
        }
    }

}

