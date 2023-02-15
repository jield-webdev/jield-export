<?php

declare(strict_types=1);

namespace Jield\Export\Columns;

use Jield\Search\Entity\HasSearchInterface;
use Solarium\QueryType\Update\Query\Document;

interface ColumnsHelperInterface
{
    public function getColumns(): array;
}
