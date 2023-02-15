<?php

declare(strict_types=1);

namespace Jield\Export\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleService
{
    private array $services = [];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function resetIndex(OutputInterface $output, string $index, bool $clearIndex = false): void
    {
    }

    public function getCores(): array
    {
        return $this->services;
    }
}
