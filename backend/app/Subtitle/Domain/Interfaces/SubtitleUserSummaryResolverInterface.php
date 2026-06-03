<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\ValueObject\SubtitleUserSummary;

interface SubtitleUserSummaryResolverInterface
{
    /**
     * @param  list<Uuid>  $userIds
     * @return array<string, SubtitleUserSummary> keyed by user uuid string
     */
    public function resolveMany(array $userIds): array;
}
