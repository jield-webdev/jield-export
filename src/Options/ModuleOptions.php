<?php

declare(strict_types=1);

namespace Jield\Export\Options;

use Laminas\Stdlib\AbstractOptions;

class ModuleOptions extends AbstractOptions
{
    protected array $entities = [];

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function setEntities(array $entities): void
    {
        $this->entities = $entities;
    }
}
