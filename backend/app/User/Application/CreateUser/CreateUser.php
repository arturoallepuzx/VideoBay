<?php

declare(strict_types=1);

namespace App\User\Application\CreateUser;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\AvatarUrl;
use App\User\Domain\ValueObject\UserName;

class CreateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(
        string $role,
        string $name,
        string $email,
        string $plainPassword,
        ?string $avatarUrl = null,
    ): CreateUserResponse {
        $emailVo = Email::create($email);

        if ($this->userRepository->existsByEmail($emailVo)) {
            throw UserEmailAlreadyExistsException::forEmail($emailVo->value());
        }

        $user = User::dddCreate(
            UserRole::create($role),
            UserName::create($name),
            $emailVo,
            $this->passwordHasher->hash($plainPassword),
            $avatarUrl !== null ? AvatarUrl::create($avatarUrl) : null,
        );

        $this->userRepository->create($user);

        return CreateUserResponse::create($user);
    }
}
