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
    public const TYPE_FLOAT = 'float';

    private array $data = [];

    public array $types = [
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_DATE,
        self::TYPE_TIME,
        self::TYPE_BOOLEAN,
        self::TYPE_FLOAT,
    ];

    public function __construct(
        private readonly string $columnName,
        private string $type = self::TYPE_STRING,
        private bool $isNullable = true
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
            case self::TYPE_BOOLEAN:

                if (is_bool($data)) {
                    //Booleans are also integers, so we need to set the type to integer
                    $this->type       = self::TYPE_INTEGER;
                    $this->isNullable = true;

                    $data = $data === true ? 1 : null;
                }

                !$this->isNullable && Assert::integer(
                    value: $data,
                    message: 'Data is not an integer for column' . $this->columnName
                );

                break;
            case self::TYPE_STRING:
            case self::TYPE_TIME:

                if ($this->type === self::TYPE_TIME) {
                    //We map the time to a string, so we need to set the type to string
                    $this->type = self::TYPE_STRING;
                }

                if ($data instanceof DateTimeInterface) {
                    $data = $data->format(format: 'H:i');
                }

                $data = TextHelpers::beautifyTextValue(value: $data);
                break;
            case self::TYPE_DATE:
                !$this->isNullable && Assert::isInstanceOf(value: $data, class: DateTimeInterface::class);
                if (null !== $data) {
                    $data = $data instanceof DateTimeImmutable ? $data : DateTimeImmutable::createFromMutable(
                        object: $data
                    );
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
