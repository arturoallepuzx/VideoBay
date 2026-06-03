<?php

declare(strict_types=1);

namespace App\Review\Application\CreateReview;

use App\Review\Domain\Entity\Review;

final readonly class CreateReviewResponse
{
    private function __construct(
        public string $id,
        public string $movieId,
        public int $rating,
        public ?string $body,
        public bool $containsSpoilers,
        public int $likesCount,
        public string $createdAt,
    ) {}

    public static function create(Review $review): self
    {
        return new self(
            id: $review->id()->value(),
            movieId: $review->movieId()->value(),
            rating: $review->rating()->value(),
            body: $review->body()?->value(),
            containsSpoilers: $review->containsSpoilers(),
            likesCount: $review->likesCount(),
            createdAt: $review->createdAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'movie_id' => $this->movieId,
            'rating' => $this->rating,
            'body' => $this->body,
            'contains_spoilers' => $this->containsSpoilers,
            'likes_count' => $this->likesCount,
            'created_at' => $this->createdAt,
        ];
    }
}
