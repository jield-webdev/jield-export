<?php

declare(strict_types=1);

namespace Jield\Export\Command;

use Jield\Export\Service\ConsoleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class SendEntity extends Command
{
    /** @var string */
    protected static $defaultName = 'export:send';

    public function __construct(private readonly ConsoleService $consoleService)
    {
        parent::__construct(name: self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setName(name: self::$defaultName);

        $cores = implode(
            separator: ', ',
            array: array_merge(
                array_keys(array: $this->consoleService->getEntities()),
                ['all']
            )
        );

        $this->addArgument(
            name: 'entity',
            mode: InputOption::VALUE_REQUIRED,
            description: $cores,
            default: 'all'
        );

        $this->addOption(
            name: 'memory-limit',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Provide a memory limit for the CLI script',
            default: '1G'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $memoryLimit = $input->getOption(name: 'memory-limit');

        ini_set(option: 'memory_limit', value: $memoryLimit);

        $entity = $input->getArgument(name: 'entity');

        $startMessage  = sprintf("<info>Send entity %s</info>", $entity);
        $memoryMessage = sprintf("Memory limit set to %s", ini_get(option: 'memory_limit'));
        $endMessage    = sprintf("<info>Sending %s completed</info>", $entity);

        $output->writeln(messages: $startMessage);
        $output->writeln(messages: $memoryMessage);

        $this->consoleService->sendEntity(output: $output, entity: $entity);

        $output->writeln(messages: $endMessage);

        return Command::SUCCESS;
    }
}
