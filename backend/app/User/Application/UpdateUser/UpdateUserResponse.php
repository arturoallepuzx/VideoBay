<?php

declare(strict_types=1);

namespace App\User\Application\UpdateUser;

use App\User\Domain\Entity\User;

final readonly class UpdateUserResponse
{
    public function __construct(
        public string $id,
        public string $role,
        public string $name,
        public string $email,
        public ?string $emailVerifiedAt,
        public ?string $avatarUrl,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            role: $user->role()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            emailVerifiedAt: $user->emailVerifiedAt()?->format(\DateTimeInterface::ATOM),
            avatarUrl: $user->avatarUrl()?->value(),
            createdAt: $user->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $user->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->emailVerifiedAt,
            'avatar_url' => $this->avatarUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
