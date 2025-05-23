<?php

declare(strict_types=1);

namespace Jield\Export\Entity;

use Jield\Export\Enum\ExportFileTypeEnum;

interface StorageLocationInterface
{
    public function getId(): int|null;

    public function getName(): string;

    public function getConnectionString(): string;

    public function getFolder(): string;

    public function getExportFileType(): ExportFileTypeEnum;

    public function getContainer(): string;
}
