<?php

declare(strict_types=1);

namespace Jield\Export\Options;

use Laminas\Stdlib\AbstractOptions;

class ModuleOptions extends AbstractOptions
{
    protected string $azureBlobStorageConnectionString = '';

    private string $blobContainer = 'dropzone';

    private string $parquetFolder = 'parquet';

    private string $excelFolder = 'excel';

    protected array $entities = [];

    public function getAzureBlobStorageConnectionString(): string
    {
        return $this->azureBlobStorageConnectionString;
    }

    public function setAzureBlobStorageConnectionString(string $azureBlobStorageConnectionString): void
    {
        $this->azureBlobStorageConnectionString = $azureBlobStorageConnectionString;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getBlobContainer(): string
    {
        return $this->blobContainer;
    }

    public function getParquetFolder(): string
    {
        return $this->parquetFolder;
    }

    public function getExcelFolder(): string
    {
        return $this->excelFolder;
    }

}
