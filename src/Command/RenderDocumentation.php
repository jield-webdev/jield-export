<?php

declare(strict_types=1);

namespace Jield\Export\Command;

use Jield\Export\Service\ConsoleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RenderDocumentation extends Command
{
    /** @var string */
    protected static $defaultName = 'search:documentation';

    public function __construct(private readonly ConsoleService $consoleService)
    {
        parent::__construct(name: self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setName(name: self::$defaultName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(messages: '<info>Write documentation</info>');

        $this->consoleService->generateDocumentation(output: $output);

        $output->writeln(
            messages: sprintf(
                "<info>Documentation for all entities has been written</info>",
            )
        );

        return Command::SUCCESS;
    }
}
