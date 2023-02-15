<?php

declare(strict_types=1);

namespace Jield\Export\ValueObject;

use codename\parquet\data\DataColumn;
use codename\parquet\data\DataField;
use Webmozart\Assert\Assert;

final class Column
{
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_DATE = 'date';

    public array $types = [
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_DATE,
    ];

    public function __construct(
        private readonly string $columnName,
        private readonly string $type,
        private array $data = []
    ) {
        Assert::inArray($type, $this->types);
    }

    public function addRow(int|string|float $data): void
    {
        $this->data[] = $data;
    }

    public function toParquetColumn(): DataColumn
    {
        return new DataColumn(
            field: DataField::createFromType(name: $this->columnName, type: $this->type),
            data: $this->data
        );
    }
}
