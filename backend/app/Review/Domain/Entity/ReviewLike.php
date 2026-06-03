<?php

declare(strict_types=1);

namespace App\Review\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class ReviewLike
{
    private function __construct(
        private Uuid $reviewId,
        private Uuid $userId,
        private DomainDateTime $createdAt,
    ) {}

    public static function dddCreate(Uuid $reviewId, Uuid $userId): self
    {
        return new self($reviewId, $userId, DomainDateTime::now());
    }

    public static function fromPersistence(
        string $reviewId,
        string $userId,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            Uuid::create($reviewId),
            Uuid::create($userId),
            DomainDateTime::create($createdAt),
        );
    }

    public function reviewId(): Uuid
    {
        return $this->reviewId;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }
}
