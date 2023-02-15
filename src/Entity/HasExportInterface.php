<?php

declare(strict_types=1);

namespace Jield\Export\Entity;

interface HasExportInterface
{
    public function getCreateExportColumnsClass(): string;

    public function getResourceId(): string;

    public function getId(): ?int;
}
