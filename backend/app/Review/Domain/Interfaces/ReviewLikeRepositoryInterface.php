<?php

declare(strict_types=1);

namespace App\Review\Domain\Interfaces;

use App\Review\Domain\Entity\ReviewLike;
use App\Shared\Domain\ValueObject\Uuid;

interface ReviewLikeRepositoryInterface
{
    public function exists(Uuid $reviewId, Uuid $userId): bool;

    public function add(ReviewLike $like): void;

    public function remove(Uuid $reviewId, Uuid $userId): bool;
}
