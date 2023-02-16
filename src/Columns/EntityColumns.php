<?php

declare(strict_types=1);

namespace Jield\Export\Columns;

use Doctrine\ORM\EntityManager;
use Jield\Export\ValueObject\Column;

abstract class EntityColumns implements ColumnsHelperInterface
{
    private string $name = 'dimentity';

    public function __construct(protected readonly EntityManager $entityManager)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<Column>
     */
    abstract public function getColumns(): array;

    abstract public function getDependencies(): array;
}
