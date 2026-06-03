<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\ValueObject\MovieSummary;

interface MovieSummaryResolverInterface
{
    /**
     * @param  list<Uuid>  $movieIds
     * @return array<string, MovieSummary> keyed by movie uuid string
     */
    public function resolveMany(array $movieIds): array;
}
