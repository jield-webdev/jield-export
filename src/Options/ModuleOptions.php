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

    public function getBlobContainer(): string
    {
        return $this->blobContainer;
    }

    public function setBlobContainer(string $blobContainer): void
    {
        $this->blobContainer = $blobContainer;
    }

    public function getParquetFolder(): string
    {
        return $this->parquetFolder;
    }

    public function setParquetFolder(string $parquetFolder): void
    {
        $this->parquetFolder = $parquetFolder;
    }

    public function getExcelFolder(): string
    {
        return $this->excelFolder;
    }

    public function setExcelFolder(string $excelFolder): void
    {
        $this->excelFolder = $excelFolder;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function setEntities(array $entities): void
    {
        $this->entities = $entities;
    }
}
