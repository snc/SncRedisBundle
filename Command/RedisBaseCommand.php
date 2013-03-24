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
 * Base command for redis interaction through the command line
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
abstract class RedisBaseCommand extends ContainerAwareCommand
{

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var mixed (Either \Predis\Client or \Snc\RedisBundle\Client\Phpredis\Client)
     */
    protected $redisClient;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->addOption(
            'client',
            null,
            InputOption::VALUE_REQUIRED, 'The name of the predis client to interact with',
            'default'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $client = $this->input->getOption('client');
        try {
            $this->redisClient = $this->getContainer()->get('snc_redis.' . $client);
        } catch (ServiceNotFoundException $e) {
            $this->output->writeln('<error>The client ' . $client . ' is not defined</error>');
            return;
        }

        $this->executeRedisCommand();
    }

    /**
     * Method which gets called by execute(). Used for code unique to the command
     */
    abstract protected function executeRedisCommand();

    /**
     * Checks if either the no-interaction option was chosen or asks the user to proceed
     *
     * @return boolean true if either no-interaction was chosen or the user wants to proceed
     */
    protected function proceedingAllowed()
    {
        if ($this->input->getOption('no-interaction')) {
            return true;
        }

        return $this->getHelper('dialog')->askConfirmation($this->output, '<question>Are you sure you wish to flush the whole database? (y/n)</question>', false);
    }
}