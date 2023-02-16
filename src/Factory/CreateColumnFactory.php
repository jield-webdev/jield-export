<?php

declare(strict_types=1);

namespace Jield\Export\Factory;

use Doctrine\ORM\EntityManager;
use Jield\Export\Columns\ColumnsHelperInterface;
use Jield\Export\Service\ConsoleService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class CreateColumnFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ColumnsHelperInterface
    {
        return new $requestedName(entityManager: $container->get(EntityManager::class));
    }
}
