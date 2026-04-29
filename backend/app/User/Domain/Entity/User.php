<?php

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;

class User
{
    private function __construct(
        private Uuid $id,
        private UserName $name,
        private Email $email,
        private PasswordHash $passwordHash,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(Email $email, UserName $name, PasswordHash $passwordHash): self
    {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $name,
            $email,
            $passwordHash,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $name,
        string $email,
        string $passwordHash,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            UserName::create($name),
            Email::create($email),
            PasswordHash::create($passwordHash),
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name->value();
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash->value();
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }
}
