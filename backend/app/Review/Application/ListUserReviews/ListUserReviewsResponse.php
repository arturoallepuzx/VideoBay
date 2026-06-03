<?php

declare(strict_types=1);

namespace App\Review\Application\ListUserReviews;

use App\Review\Domain\Entity\Review;

final readonly class ListUserReviewsResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /**
     * @param  array{items: list<Review>, total: int, page: int, totalPages: int}  $result
     */
    public static function create(array $result): self
    {
        $items = array_map(
            fn (Review $review): array => self::reviewToArray($review),
            $result['items'],
        );

        return new self(
            items: array_values($items),
            page: $result['page'],
            totalPages: $result['totalPages'],
            total: $result['total'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'page' => $this->page,
            'total_pages' => $this->totalPages,
            'total' => $this->total,
        ];
    }

    /** @return array<string, mixed> */
    private static function reviewToArray(Review $review): array
    {
        return [
            'id' => $review->id()->value(),
            'movie_id' => $review->movieId()->value(),
            'rating' => $review->rating()->value(),
            'body' => $review->body()?->value(),
            'contains_spoilers' => $review->containsSpoilers(),
            'likes_count' => $review->likesCount(),
            'created_at' => $review->createdAt()->value()->format(\DateTimeInterface::ATOM),
            'updated_at' => $review->updatedAt()->value()->format(\DateTimeInterface::ATOM),
        ];
    }
}
