<?php

declare(strict_types=1);

namespace Jield\Export\Entity;

interface StorageLocationInterface
{
    public function getConnectionString(): string;

    public function getExcelFolder(): string;

    public function getParquetFolder(): string;

    public function getContainer(): string;

    public function getOAuth2Service(): mixed;
}
