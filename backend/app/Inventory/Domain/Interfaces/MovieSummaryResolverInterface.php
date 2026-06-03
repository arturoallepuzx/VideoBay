<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Interfaces;

use App\Inventory\Domain\ValueObject\MovieSummary;
use App\Shared\Domain\ValueObject\Uuid;

interface MovieSummaryResolverInterface
{
    /**
     * @param  list<Uuid>  $movieIds
     * @return array<string, MovieSummary> keyed by movie uuid string
     */
    public function resolveMany(array $movieIds): array;
}
