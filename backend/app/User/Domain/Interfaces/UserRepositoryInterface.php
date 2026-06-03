<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function create(User $user): void;

    public function update(User $user): void;

    public function delete(Uuid $id): void;

    public function findById(Uuid $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function existsByEmail(Email $email): bool;

    public function existsByEmailExcludingId(Email $email, Uuid $excludeUserId): bool;

    /** @return User[] */
    public function findAll(): array;
}
