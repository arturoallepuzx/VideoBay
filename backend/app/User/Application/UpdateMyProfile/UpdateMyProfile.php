<?php

declare(strict_types=1);

namespace App\User\Application\UpdateMyProfile;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\AvatarUrl;
use App\User\Domain\ValueObject\UserName;

class UpdateMyProfile
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(
        string $userId,
        ?string $name,
        ?string $email,
        ?string $avatarUrl,
        bool $avatarUrlProvided,
    ): UpdateMyProfileResponse {
        $userUuid = Uuid::create($userId);

        $user = $this->userRepository->findById($userUuid);

        if ($user === null) {
            throw UserNotFoundException::forId($userUuid);
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

        if ($avatarUrlProvided) {
            $user->updateAvatarUrl($avatarUrl !== null ? AvatarUrl::create($avatarUrl) : null);
        }

        if ($user->wasModified()) {
            $this->userRepository->update($user);
        }

        return UpdateMyProfileResponse::create($user);
    }
}
