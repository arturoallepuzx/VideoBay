<?php

declare(strict_types=1);

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\ValueObject\CopyDetails;
use App\Shared\Domain\ValueObject\Uuid;

interface CopyDetailsProviderInterface
{
    /**
     * @param  list<Uuid>  $physicalCopyIds
     * @return array<string, CopyDetails>
     */
    public function getByIds(array $physicalCopyIds): array;
}
