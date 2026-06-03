<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\Entity\WatchLaterItem;

interface WatchLaterItemRepositoryInterface
{
    public function exists(Uuid $userId, Uuid $movieId): bool;

    public function add(WatchLaterItem $item): bool;

    public function remove(Uuid $userId, Uuid $movieId): bool;

    /**
     * @return array{items: list<WatchLaterItem>, total: int, page: int, totalPages: int}
     */
    public function listByUser(Uuid $userId, int $page, int $perPage): array;

    /**
     * @return list<Uuid>
     */
    public function listUserIdsByMovie(Uuid $movieId): array;
}
