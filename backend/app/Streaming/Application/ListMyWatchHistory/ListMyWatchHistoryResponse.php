<?php

declare(strict_types=1);

namespace App\Streaming\Application\ListMyWatchHistory;

use App\Streaming\Domain\Entity\PlaybackProgress;

final readonly class ListMyWatchHistoryResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /** @param array{items: list<PlaybackProgress>, total: int, page: int, totalPages: int} $result */
    public static function create(array $result): self
    {
        $items = array_map(
            fn (PlaybackProgress $p): array => self::progressToArray($p),
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
    private static function progressToArray(PlaybackProgress $progress): array
    {
        return [
            'movie_id' => $progress->movieId()->value(),
            'duration_seconds' => $progress->durationSeconds(),
            'completed_at' => $progress->updatedAt()->value()->format(\DateTimeInterface::ATOM),
        ];
    }
}
