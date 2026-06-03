<?php

declare(strict_types=1);

namespace App\User\Application\ListUsersWithActiveSessions;

use App\User\Domain\Interfaces\UserActiveSessionsFinderInterface;

class ListUsersWithActiveSessions
{
    public function __construct(
        private UserActiveSessionsFinderInterface $userActiveSessionsFinder,
    ) {}

    public function __invoke(): ListUsersWithActiveSessionsResponse
    {
        return ListUsersWithActiveSessionsResponse::create(
            $this->userActiveSessionsFinder->findUsersWithActiveSessions()
        );
    }
}
