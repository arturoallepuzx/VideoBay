<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Interfaces;

use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\ValueObject\SkuCode;
use App\Shared\Domain\ValueObject\Uuid;

interface PhysicalCopyRepositoryInterface
{
    public function create(PhysicalCopy $copy): void;

    public function update(PhysicalCopy $copy): void;

    public function findByUuid(Uuid $uuid): ?PhysicalCopy;

    public function findByUuidForUpdate(Uuid $uuid): ?PhysicalCopy;

    /**
     * @param  list<Uuid>  $uuids
     * @return array<string, PhysicalCopy>
     */
    public function findManyByUuids(array $uuids): array;

    public function existsBySku(SkuCode $sku): bool;

    /**
     * @return array{copies: list<PhysicalCopy>, total: int, page: int, totalPages: int}
     */
    public function listAvailable(?Uuid $movieId, int $page, int $perPage): array;
}
