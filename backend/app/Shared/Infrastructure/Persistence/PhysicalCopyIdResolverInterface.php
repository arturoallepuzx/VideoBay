<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\ValueObject\Uuid;

interface PhysicalCopyIdResolverInterface
{
    public function toInternalId(Uuid $copyUuid): int;

    public function toDomainUuid(int $internalId): Uuid;
}
