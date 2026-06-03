<?php

declare(strict_types=1);

namespace App\User\Application\DeleteMyAccount;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class DeleteMyAccount
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserAuthenticationGlobalRevokerInterface $authenticationRevoker,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $userId): void
    {
        $userUuid = Uuid::create($userId);

        $user = $this->userRepository->findById($userUuid);

        if ($user === null) {
            throw UserNotFoundException::forId($userUuid);
        }

        $this->transactionRunner->run(function () use ($user, $userUuid): void {
            $user->anonymize();
            $this->userRepository->update($user);
            $this->userRepository->delete($userUuid);
            $this->authenticationRevoker->revokeAllByUserId($userUuid);
        });
    }
}
