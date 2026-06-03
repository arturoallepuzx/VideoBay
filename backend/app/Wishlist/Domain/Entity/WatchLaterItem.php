<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class WatchLaterItem
{
    private function __construct(
        private Uuid $userId,
        private Uuid $movieId,
        private DomainDateTime $addedAt,
    ) {}

    public static function dddCreate(Uuid $userId, Uuid $movieId): self
    {
        return new self($userId, $movieId, DomainDateTime::now());
    }

    public static function fromPersistence(
        string $userId,
        string $movieId,
        \DateTimeImmutable $addedAt,
    ): self {
        return new self(
            Uuid::create($userId),
            Uuid::create($movieId),
            DomainDateTime::create($addedAt),
        );
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function addedAt(): DomainDateTime
    {
        return $this->addedAt;
    }
}
