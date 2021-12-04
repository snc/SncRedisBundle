<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

use function array_shift;
use function in_array;
use function is_iterable;
use function strtolower;

class RedisQueryCommand extends Command
{
    public const COMMAND_NAME = 'redis:query';

    private ContainerInterface $clientLocator;
    private DataDumperInterface $dumper;
    private ClonerInterface $cloner;

    private const NON_CLUSTER_COMMANDS_ONLY = [
        'flushdb',
        'flushall',
        'keys',
    ];

    public function __construct(ContainerInterface $clientLocator, ?DataDumperInterface $dumper, ?ClonerInterface $cloner)
    {
        parent::__construct();
        $this->clientLocator = $clientLocator;
        $this->dumper        = $dumper ?: new CliDumper();
        $this->cloner        = $cloner ?: new VarCloner();
    }

    public function setClientLocator(ContainerInterface $clientLocator): void
    {
        $this->clientLocator = $clientLocator;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Queries redis client with custom command and dumps the result to output.')
            ->addArgument('query', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Redis query, eg. "flushall"')
            ->addOption(
                'client',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of the snc_redis client to interact with',
                'default',
            )
            ->addUsage('flushall')
            ->addUsage('keys "*"')
            ->addUsage('del foo_bar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client          = $input->getOption('client');
        $command         = $input->getArgument('query');
        $method          = strtolower(array_shift($command));
        $canRunInCluster = !in_array($method, self::NON_CLUSTER_COMMANDS_ONLY, true);

        try {
            $client = $this->clientLocator->get($client);
        } catch (ServiceNotFoundException $e) {
            $output->writeln('<error>The client "' . $client . '" is not defined</error>');

            return 1;
        }

        $clients = $canRunInCluster || !is_iterable($client) ? [$client] : $client;

        foreach ($clients as $client) {
            $this->dumper->dump($this->cloner->cloneVar($client->$method(...$command)));
        }

        return 0;
    }
}
