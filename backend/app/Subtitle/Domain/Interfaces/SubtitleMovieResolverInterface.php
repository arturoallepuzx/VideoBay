<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\ValueObject\SubtitleMovieMetadata;

interface SubtitleMovieResolverInterface
{
    public function resolve(Uuid $movieId): SubtitleMovieMetadata;
}
