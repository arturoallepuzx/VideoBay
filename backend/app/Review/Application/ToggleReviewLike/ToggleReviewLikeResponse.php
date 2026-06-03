<?php

declare(strict_types=1);

namespace App\Review\Application\ToggleReviewLike;

final readonly class ToggleReviewLikeResponse
{
    private function __construct(
        public string $reviewId,
        public bool $liked,
        public int $likesCount,
    ) {}

    public static function create(string $reviewId, bool $liked, int $likesCount): self
    {
        return new self($reviewId, $liked, $likesCount);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'review_id' => $this->reviewId,
            'liked' => $this->liked,
            'likes_count' => $this->likesCount,
        ];
    }
}
