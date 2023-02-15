<?php

declare(strict_types=1);

namespace Jield\Export\Options;

use Laminas\Stdlib\AbstractOptions;

class ModuleOptions extends AbstractOptions
{
    protected string $azureBlobStorageConnectionString = '';

    public function getAzureBlobStorageConnectionString(): string
    {
        return $this->azureBlobStorageConnectionString;
    }

    public function setAzureBlobStorageConnectionString(string $azureBlobStorageConnectionString): void
    {
        $this->azureBlobStorageConnectionString = $azureBlobStorageConnectionString;
    }

}
