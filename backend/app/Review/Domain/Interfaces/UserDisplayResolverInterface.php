<?php

declare(strict_types=1);

namespace App\Review\Domain\Interfaces;

use App\Review\Domain\ValueObject\UserDisplay;
use App\Shared\Domain\ValueObject\Uuid;

interface UserDisplayResolverInterface
{
    /**
     * @param  list<Uuid>  $userIds
     * @return array<string, UserDisplay>
     */
    public function resolveMany(array $userIds): array;
}
