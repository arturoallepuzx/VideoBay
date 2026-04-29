<?php

namespace App\User\Application\CreateUser;

use App\User\Domain\Entity\User;

final readonly class CreateUserResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            name: $user->name(),
            email: $user->email()->value(),
            createdAt: $user->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $user->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
