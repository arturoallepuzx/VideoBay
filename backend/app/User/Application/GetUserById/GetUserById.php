<?php

declare(strict_types=1);

namespace App\User\Application\GetUserById;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetUserById
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $id): GetUserByIdResponse
    {
        $userId = Uuid::create($id);

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw UserNotFoundException::forId($userId);
        }

        return GetUserByIdResponse::create($user);
    }
}
