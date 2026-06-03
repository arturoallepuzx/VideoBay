<?php

declare(strict_types=1);

namespace App\Review\Domain\Interfaces;

use App\Review\Domain\Entity\Review;
use App\Shared\Domain\ValueObject\Uuid;

interface ReviewRepositoryInterface
{
    public function findByUuid(Uuid $uuid): ?Review;

    public function findByUuidForUpdate(Uuid $uuid): ?Review;

    public function findByUserAndMovie(Uuid $userId, Uuid $movieId): ?Review;

    public function create(Review $review): void;

    public function update(Review $review): void;

    /**
     * @return array{items: list<Review>, total: int, page: int, totalPages: int}
     */
    public function listByMovie(Uuid $movieId, int $page, int $perPage): array;

    /**
     * @return array{items: list<Review>, total: int, page: int, totalPages: int}
     */
    public function listByUser(Uuid $userId, int $page, int $perPage): array;

    /**
     * @param  list<Uuid>  $uuids
     * @return list<Review>
     */
    public function findManyByUuids(array $uuids): array;
}
