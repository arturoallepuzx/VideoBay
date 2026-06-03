<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface MovieResolverForStreamingInterface
{
    public function resolveByUuid(Uuid $uuid): Uuid;

    public function resolveByTmdbId(int $tmdbId): Uuid;
}
