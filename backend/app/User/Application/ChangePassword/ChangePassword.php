<?php

declare(strict_types=1);

namespace App\User\Application\ChangePassword;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class ChangePassword
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private UserAuthenticationGlobalRevokerInterface $authenticationRevoker,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(
        string $userId,
        string $currentPassword,
        string $newPassword,
    ): void {
        $userUuid = Uuid::create($userId);

        $user = $this->userRepository->findById($userUuid);

        if ($user === null) {
            throw UserNotFoundException::forId($userUuid);
        }

        if (! $this->passwordHasher->verify($currentPassword, $user->passwordHash())) {
            throw InvalidCredentialsException::invalid();
        }

        $user->changePassword($this->passwordHasher->hash($newPassword));

        $this->transactionRunner->run(function () use ($user, $userUuid): void {
            $this->userRepository->update($user);
            $this->authenticationRevoker->revokeAllByUserId($userUuid);
        });
    }
}
