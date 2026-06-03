<?php

declare(strict_types=1);

namespace App\User\Application\ListUsers;

use App\User\Domain\Interfaces\UserRepositoryInterface;

class ListUsers
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(): ListUsersResponse
    {
        $users = $this->userRepository->findAll();

        return ListUsersResponse::create($users);
    }
}
