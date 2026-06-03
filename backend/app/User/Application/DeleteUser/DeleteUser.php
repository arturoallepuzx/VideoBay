<?php

declare(strict_types=1);

namespace App\User\Application\DeleteUser;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class DeleteUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserAuthenticationGlobalRevokerInterface $authenticationRevoker,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $id): void
    {
        $userId = Uuid::create($id);

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw UserNotFoundException::forId($userId);
        }

        $this->transactionRunner->run(function () use ($user, $userId): void {
            $user->anonymize();
            $this->userRepository->update($user);
            $this->userRepository->delete($userId);
            $this->authenticationRevoker->revokeAllByUserId($userId);
        });
    }
}
