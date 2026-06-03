<?php

declare(strict_types=1);

namespace App\User\Application\LoginUser;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserAuthenticationIssuerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\AuthenticationSubject;

class LoginUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private UserAuthenticationIssuerInterface $userAuthenticationIssuer,
    ) {}

    public function __invoke(
        string $email,
        string $plainPassword,
    ): LoginUserResponse {
        $emailVO = Email::create($email);

        $user = $this->userRepository->findByEmail($emailVO);

        if ($user === null || ! $this->passwordHasher->verify($plainPassword, $user->passwordHash())) {
            throw InvalidCredentialsException::invalid();
        }

        $issuedAuthentication = $this->userAuthenticationIssuer->issueFor(
            AuthenticationSubject::create(
                $user->id(),
                $user->role(),
            )
        );

        return LoginUserResponse::create($user, $issuedAuthentication);
    }
}
