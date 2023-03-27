<?php

declare(strict_types=1);

namespace Jield\Export\Service;

use Jield\Export\Entity\StorageLocationInterface;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

interface StorageLocationServiceInterface
{
    public function getDefaultStorageLocation(): StorageLocationInterface;

    public function getBlobService(): BlobRestProxy;
}
