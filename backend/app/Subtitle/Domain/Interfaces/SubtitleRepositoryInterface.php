<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Entity\Subtitle;

interface SubtitleRepositoryInterface
{
    public function create(Subtitle $subtitle): void;

    public function update(Subtitle $subtitle): void;

    public function findByUuid(Uuid $uuid): ?Subtitle;

    public function findByUuidForUpdate(Uuid $uuid): ?Subtitle;

    public function findByProviderAndExternalId(string $provider, string $externalId): ?Subtitle;

    /**
     * @param  list<Uuid>  $uuids
     * @return array<string, Subtitle>
     */
    public function findManyByUuids(array $uuids): array;

    /**
     * @return list<Subtitle>
     */
    public function listByMovie(Uuid $movieId): array;
}
