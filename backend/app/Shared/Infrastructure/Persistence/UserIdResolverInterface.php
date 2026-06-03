<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\ValueObject\Uuid;

interface UserIdResolverInterface
{
    public function toInternalId(Uuid $userUuid): int;

    public function toDomainUuid(int $internalId): Uuid;
}
