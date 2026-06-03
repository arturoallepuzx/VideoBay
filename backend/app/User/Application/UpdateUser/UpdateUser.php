<?php

declare(strict_types=1);

namespace App\User\Application\UpdateUser;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\AvatarUrl;
use App\User\Domain\ValueObject\UserName;

class UpdateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(
        string $id,
        ?string $name,
        ?string $email,
        ?string $role,
        ?string $avatarUrl,
        bool $avatarUrlProvided,
    ): UpdateUserResponse {
        $userId = Uuid::create($id);

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw UserNotFoundException::forId($userId);
        }

        if ($name !== null) {
            $user->updateName(UserName::create($name));
        }

        if ($email !== null) {
            $newEmail = Email::create($email);

            if (! $newEmail->equals($user->email())) {
                if ($this->userRepository->existsByEmailExcludingId($newEmail, $user->id())) {
                    throw UserEmailAlreadyExistsException::forEmail($newEmail->value());
                }

                $user->updateEmail($newEmail);
            }
        }

        if ($role !== null) {
            $user->updateRole(UserRole::create($role));
        }

        if ($avatarUrlProvided) {
            $user->updateAvatarUrl($avatarUrl !== null ? AvatarUrl::create($avatarUrl) : null);
        }

        if ($user->wasModified()) {
            $this->userRepository->update($user);
        }

        return UpdateUserResponse::create($user);
    }
}
