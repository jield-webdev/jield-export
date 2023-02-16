<?php

declare(strict_types=1);

namespace Jield\Export\Command;

use Jield\Export\Columns\ColumnsHelperInterface;
use Jield\Export\Service\ConsoleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListEntities extends Command
{
    /** @var string */
    protected static $defaultName = 'search:list';

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
        $output->writeln(messages: '<info>List of all entities in index</info>');
        $entities = $this->consoleService->getEntities();

        foreach ($entities as $key => $entityName) {
            //Try to instantiate the core and see if we have a valid entity
            try {
                $entity = new $entityName();
                if ($entity instanceof ColumnsHelperInterface) {
                    $output->writeln(
                        messages: sprintf("Entity for %s (%s) is OK", $key, $entityName)
                    );
                } else {
                    $output->writeln(
                        messages: sprintf(
                            "Entity for %s (%s) does nit implement ColumnsHelperInterface",
                            $key,
                            $entityName
                        )
                    );
                }
            } catch (\Exception $e) {
                $output->writeln(
                    messages: sprintf("Entity for %s (%s) is not valid", $key, $entityName)
                );
                continue;
            }
        }

        $output->writeln(messages: sprintf("<info>In total %d entities are active</info>", count($entities)));

        return Command::SUCCESS;
    }
}
