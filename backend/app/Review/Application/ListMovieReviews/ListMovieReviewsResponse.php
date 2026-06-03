<?php

declare(strict_types=1);

namespace App\Review\Application\ListMovieReviews;

use App\Review\Domain\Entity\Review;
use App\Review\Domain\ValueObject\UserDisplay;

final readonly class ListMovieReviewsResponse
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
     * @param  array<string, UserDisplay>  $authorDisplays
     */
    public static function create(array $result, array $authorDisplays): self
    {
        $items = array_map(
            fn (Review $review): array => self::reviewToArray($review, $authorDisplays),
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

    /**
     * @param  array<string, UserDisplay>  $authorDisplays
     * @return array<string, mixed>
     */
    private static function reviewToArray(Review $review, array $authorDisplays): array
    {
        $author = $authorDisplays[$review->userId()->value()] ?? null;

        return [
            'id' => $review->id()->value(),
            'rating' => $review->rating()->value(),
            'body' => $review->body()?->value(),
            'contains_spoilers' => $review->containsSpoilers(),
            'likes_count' => $review->likesCount(),
            'created_at' => $review->createdAt()->value()->format(\DateTimeInterface::ATOM),
            'updated_at' => $review->updatedAt()->value()->format(\DateTimeInterface::ATOM),
            'author' => $author !== null
                ? ['uuid' => $author->uuid(), 'name' => $author->name(), 'avatar_url' => $author->avatarUrl()]
                : null,
        ];
    }
}
