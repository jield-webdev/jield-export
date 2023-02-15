<?php

declare(strict_types=1);

namespace Jield\Export\Service;

use Jield\Search\Entity\HasSearchInterface;
use Jield\Search\Service\AbstractSearchService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class ConsoleService
{
    private array $services = [];

    public function __construct(private readonly ContainerInterface $container)
    {
        $services = $container->get('config')['export']['entities'];

        //Do an init check
        foreach ($services ?? [] as $key => $service) {
            if (!$this->container->has($service['service'])) {
                throw new InvalidArgumentException(message: sprintf('Service %s not found', $service['service']));
            }

            Assert::isInstanceOf(value: new $service['entity'](), class: HasSearchInterface::class);

            $this->services[$key] = $service;
        }
    }

    public function resetIndex(OutputInterface $output, string $index, bool $clearIndex = false): void
    {
        if ($index === 'all') {
            foreach ($this->services as $service) {
                /** @var AbstractSearchService $serviceInstance */
                $serviceInstance = $this->container->get($service['service']);
                /** @var HasSearchInterface $entity */
                $entity = new $service['entity']();

                $limit = $service['limit'] ?? 50;
                $criteria = $service['criteria'] ?? [];

                $serviceInstance->updateCollection(
                    output: $output,
                    entity: $entity,
                    clearIndex: $clearIndex,
                    limit: $limit,
                    criteria: $criteria
                );
            }
            return;
        }

        if (!isset($this->services[$index])) {
            $output->writeln(messages: sprintf('<error>Index %s not found</error>', $index));

            return;
        }

        $output->writeln(messages: sprintf('<info>Updating index %s</info>', $index));

        //We have the service, so get it
        /** @var AbstractSearchService $serviceInstance */
        $serviceInstance = $this->container->get($this->services[$index]['service']);
        /** @var HasSearchInterface $entity */
        $entity = new $this->services[$index]['entity']();

        $limit = $this->services[$index]['limit'] ?? 50;
        $criteria = $this->services[$index]['criteria'] ?? [];

        $serviceInstance->updateCollection(
            output: $output,
            entity: $entity,
            clearIndex: $clearIndex,
            limit: $limit,
            criteria: $criteria
        );
    }

    public function getCores(): array
    {
        return $this->services;
    }
}
