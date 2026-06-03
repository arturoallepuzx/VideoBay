<?php

declare(strict_types=1);

namespace App\Review\Application\UpdateReview;

use App\Review\Domain\Entity\Review;

final readonly class UpdateReviewResponse
{
    private function __construct(
        public string $id,
        public int $rating,
        public ?string $body,
        public bool $containsSpoilers,
        public string $updatedAt,
    ) {}

    public static function create(Review $review): self
    {
        return new self(
            id: $review->id()->value(),
            rating: $review->rating()->value(),
            body: $review->body()?->value(),
            containsSpoilers: $review->containsSpoilers(),
            updatedAt: $review->updatedAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'body' => $this->body,
            'contains_spoilers' => $this->containsSpoilers,
            'updated_at' => $this->updatedAt,
        ];
    }
}
