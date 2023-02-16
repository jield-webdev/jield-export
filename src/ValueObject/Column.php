<?php

declare(strict_types=1);

namespace Jield\Export\ValueObject;

use codename\parquet\data\DataColumn;
use codename\parquet\data\DataField;
use codename\parquet\data\DateTimeDataField;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Jield\Export\Helper\TextHelpers;
use Webmozart\Assert\Assert;

final class Column
{
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_DATE = 'date';
    public const TYPE_TIME = 'time';
    public const TYPE_BOOLEAN = 'boolean';

    private array $data = [];

    public array $types = [
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_DATE,
    ];

    public function __construct(
        private readonly string $columnName,
        private string $type = self::TYPE_STRING,
        private readonly bool $isNullable = true
    ) {
        Assert::inArray(value: $type, values: $this->types);
    }

    public function addRow(int|string|float|null|bool|DateTime|DateTimeImmutable $data): void
    {
        if (!$this->isNullable && $data === null) {
            throw new InvalidArgumentException(
                message: 'Data cannot be null, column: ' . $this->columnName . ' type: ' . $this->type . ' is null'
            );
        }

        switch ($this->type) {
            case self::TYPE_INTEGER:
                !$this->isNullable && Assert::integer(value: $data);
                break;
            case self::TYPE_STRING:
                $data = TextHelpers::beautifyTextValue(value: $data);
                break;
            case self::TYPE_BOOLEAN:
                Assert::boolean(value: $data);
                $data       = $data === true ? 1 : null;
                $this->type = self::TYPE_INTEGER; //Set to integer
                break;
            case self::TYPE_DATE:
                !$this->isNullable && Assert::isInstanceOf(value: $data, class: DateTimeInterface::class);
                if (null !== $data) {
                    $data = $data instanceof DateTime ? $data : DateTime::createFromImmutable(object: $data);
                }
                break;
            case self::TYPE_TIME:
                !$this->isNullable && Assert::implementsInterface(value: $data, interface: DateTimeInterface::class);
                if (null !== $data) {
                    $data = $data->format(format: 'H:i');
                }
                break;
        }
        $this->data[] = $data;
    }

    public function toParquetColumn(): DataColumn
    {
        if ($this->type === self::TYPE_DATE) {
            $field = DateTimeDataField::create(name: $this->columnName, format: 4);
        } else {
            $field = DataField::createFromType(name: $this->columnName, type: $this->type);
        }

        return new DataColumn(
            field: $field,
            data: $this->data
        );
    }
}
