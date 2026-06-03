<?php

declare(strict_types=1);

namespace App\User\Application\ListUsers;

use App\User\Domain\Entity\User;

final readonly class ListUsersResponse
{
    /** @param array<int, array<string, mixed>> $users */
    public function __construct(
        public array $users,
    ) {}

    /** @param User[] $users */
    public static function create(array $users): self
    {
        return new self(
            users: array_map(fn (User $user): array => [
                'id' => $user->id()->value(),
                'role' => $user->role()->value(),
                'name' => $user->name()->value(),
                'email' => $user->email()->value(),
                'email_verified_at' => $user->emailVerifiedAt()?->format(\DateTimeInterface::ATOM),
                'avatar_url' => $user->avatarUrl()?->value(),
                'created_at' => $user->createdAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $user->updatedAt()->format(\DateTimeInterface::ATOM),
            ], $users),
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return $this->users;
    }
}
