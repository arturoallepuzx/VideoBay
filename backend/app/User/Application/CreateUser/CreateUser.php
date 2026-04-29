<?php

namespace App\User\Application\CreateUser;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;

class CreateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $email, string $name, string $plainPassword): CreateUserResponse
    {
        $emailVO = Email::create($email);
        $nameVO = UserName::create($name);
        $passwordHashVO = PasswordHash::create($this->passwordHasher->hash($plainPassword));
        $user = User::dddCreate($emailVO, $nameVO, $passwordHashVO);
        $this->userRepository->save($user);

        return CreateUserResponse::create($user);
    }
}
