<?php

declare(strict_types=1);

namespace App\Review\Domain\Event;

use App\Shared\Domain\ValueObject\Uuid;

class ReviewLiked
{
    private function __construct(
        private Uuid $reviewId,
        private Uuid $reviewAuthorId,
        private Uuid $likedByUserId,
        private Uuid $movieId,
        private ?string $reviewBody,
    ) {}

    public static function create(Uuid $reviewId, Uuid $reviewAuthorId, Uuid $likedByUserId, Uuid $movieId, ?string $reviewBody): self
    {
        return new self($reviewId, $reviewAuthorId, $likedByUserId, $movieId, $reviewBody);
    }

    public function reviewId(): Uuid
    {
        return $this->reviewId;
    }

    public function reviewAuthorId(): Uuid
    {
        return $this->reviewAuthorId;
    }

    public function likedByUserId(): Uuid
    {
        return $this->likedByUserId;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function reviewBody(): ?string
    {
        return $this->reviewBody;
    }
}
