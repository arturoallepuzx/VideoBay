<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\ValueObject\SubtitleMovieSummary;

interface SubtitleMovieSummaryResolverInterface
{
    /**
     * @param  list<Uuid>  $movieIds
     * @return array<string, SubtitleMovieSummary> keyed by movie uuid string
     */
    public function resolveMany(array $movieIds): array;
}
