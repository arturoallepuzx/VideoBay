<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\User\Domain\ValueObject\UserActiveSessionsSummary;

interface UserActiveSessionsFinderInterface
{
    /**
     * @return list<UserActiveSessionsSummary>
     */
    public function findUsersWithActiveSessions(): array;
}
