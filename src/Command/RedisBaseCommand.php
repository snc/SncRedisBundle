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

use Predis\Client;
use Psr\Container\ContainerInterface;
use Redis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Base command for redis interaction through the command line
 */
abstract class RedisBaseCommand extends Command
{
    protected ContainerInterface $clientLocator;

    public function setClientLocator(ContainerInterface $clientLocator): void
    {
        $this->clientLocator = $clientLocator;
    }

    protected InputInterface $input;

    protected OutputInterface $output;

    /** @var Client|Redis */
    protected $redisClient;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->addOption(
            'client',
            null,
            InputOption::VALUE_REQUIRED,
            'The name of the predis client to interact with',
            'default'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        $client = $this->input->getOption('client');
        try {
            $this->redisClient = $this->clientLocator->get('snc_redis.' . $client);
        } catch (ServiceNotFoundException $e) {
            $this->output->writeln('<error>The client "' . $client . '" is not defined</error>');

            return 0;
        }

        return $this->executeRedisCommand();
    }

    /**
     * Method which gets called by execute(). Used for code unique to the command
     */
    abstract protected function executeRedisCommand(): int;

    /**
     * Checks if either the no-interaction option was chosen or asks the user to proceed
     *
     * @return bool true if either no-interaction was chosen or the user wants to proceed
     */
    protected function proceedingAllowed(): bool
    {
        if ($this->input->getOption('no-interaction')) {
            return true;
        }

        return $this->getHelper('question')->ask($this->input, $this->output, new ConfirmationQuestion('<question>Are you sure you wish to flush the whole database? (y/n)</question>', false));
    }
}
