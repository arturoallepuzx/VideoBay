<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\ValueObject\Uuid;

interface MovieIdResolverInterface
{
    public function toInternalId(Uuid $movieUuid): int;

    public function toDomainUuid(int $internalId): Uuid;
}
