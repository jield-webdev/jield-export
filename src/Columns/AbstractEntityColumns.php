<?php

declare(strict_types=1);

namespace Jield\Export\Columns;

use Doctrine\ORM\EntityManager;
use Jield\Export\ValueObject\Column;

abstract class AbstractEntityColumns implements ColumnsHelperInterface
{
    protected string $name = 'dimentity';

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

    /**
     * Default empty array.
     *
     * @return array<string, string>
     */
    public function getDependencies(): array
    {
        return [];
    }
}
