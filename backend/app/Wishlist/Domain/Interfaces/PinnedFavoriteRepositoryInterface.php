<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\Entity\PinnedFavorite;

interface PinnedFavoriteRepositoryInterface
{
    /**
     * @return list<PinnedFavorite>
     */
    public function listByUser(Uuid $userId): array;

    /**
     * @param  list<PinnedFavorite>  $pins
     */
    public function replaceAllForUser(Uuid $userId, array $pins): void;
}
