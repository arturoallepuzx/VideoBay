<?php

declare(strict_types=1);

namespace App\Review\Domain\Entity;

use App\Review\Domain\ValueObject\ReviewBody;
use App\Review\Domain\ValueObject\ReviewRating;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Review
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private Uuid $userId,
        private Uuid $movieId,
        private ReviewRating $rating,
        private ?ReviewBody $body,
        private bool $containsSpoilers,
        private int $likesCount,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt,
    ) {}

    public static function dddCreate(
        Uuid $userId,
        Uuid $movieId,
        ReviewRating $rating,
        ?ReviewBody $body,
        bool $containsSpoilers,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $userId,
            $movieId,
            $rating,
            $body,
            $containsSpoilers,
            0,
            $now,
            $now,
            null,
        );
    }

    public static function fromPersistence(
        string $id,
        string $userId,
        string $movieId,
        int $rating,
        ?string $body,
        bool $containsSpoilers,
        int $likesCount,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($userId),
            Uuid::create($movieId),
            ReviewRating::create($rating),
            $body !== null ? ReviewBody::create($body) : null,
            $containsSpoilers,
            $likesCount,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
            $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
        );
    }

    public function updateRating(ReviewRating $rating): void
    {
        if ($this->rating->equals($rating)) {
            return;
        }

        $this->rating = $rating;
        $this->touch();
    }

    public function updateBody(?ReviewBody $body): void
    {
        if ($body === null && $this->body === null) {
            return;
        }

        if ($body !== null && $this->body !== null && $this->body->equals($body)) {
            return;
        }

        $this->body = $body;
        $this->touch();
    }

    public function updateContainsSpoilers(bool $containsSpoilers): void
    {
        if ($this->containsSpoilers === $containsSpoilers) {
            return;
        }

        $this->containsSpoilers = $containsSpoilers;
        $this->touch();
    }

    public function softDelete(): void
    {
        if ($this->deletedAt !== null) {
            return;
        }

        $now = DomainDateTime::now();
        $this->deletedAt = $now;
        $this->touch();
    }

    public function isOwnedBy(Uuid $userId): bool
    {
        return $this->userId->equals($userId);
    }

    public function incrementLikes(): void
    {
        $this->likesCount++;
        $this->touch();
    }

    public function decrementLikes(): void
    {
        if ($this->likesCount > 0) {
            $this->likesCount--;
            $this->touch();
        }
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function rating(): ReviewRating
    {
        return $this->rating;
    }

    public function body(): ?ReviewBody
    {
        return $this->body;
    }

    public function containsSpoilers(): bool
    {
        return $this->containsSpoilers;
    }

    public function likesCount(): int
    {
        return $this->likesCount;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
