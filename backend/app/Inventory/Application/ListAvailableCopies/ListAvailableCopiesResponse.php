<?php

declare(strict_types=1);

namespace App\Inventory\Application\ListAvailableCopies;

use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\ValueObject\MovieSummary;

final readonly class ListAvailableCopiesResponse
{
    /** @param list<array<string, mixed>> $copies */
    private function __construct(
        public array $copies,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /**
     * @param  array{copies: list<PhysicalCopy>, total: int, page: int, totalPages: int}  $result
     * @param  array<string, MovieSummary>  $movieSummaries
     */
    public static function create(array $result, array $movieSummaries): self
    {
        $copies = array_map(
            function (PhysicalCopy $copy) use ($movieSummaries): array {
                $movieId = $copy->movieId()->value();
                $movie = $movieSummaries[$movieId] ?? null;

                return [
                    'id' => $copy->id()->value(),
                    'movie_id' => $movieId,
                    'movie_title' => $movie?->title(),
                    'poster_path' => $movie?->posterPath(),
                    'sku' => $copy->sku()->value(),
                    'barcode' => $copy->barcode()?->value(),
                    'format' => $copy->format()->value(),
                    'region' => $copy->region()?->value(),
                    'condition' => $copy->condition()->value(),
                    'cover_photo_url' => $copy->coverPhotoUrl(),
                    'price_cents' => $copy->price()->cents(),
                    'currency' => $copy->price()->currency(),
                    'stock_available' => $copy->stockAvailable()->value(),
                ];
            },
            $result['copies'],
        );

        return new self(
            copies: array_values($copies),
            page: $result['page'],
            totalPages: $result['totalPages'],
            total: $result['total'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'copies' => $this->copies,
            'page' => $this->page,
            'total_pages' => $this->totalPages,
            'total' => $this->total,
        ];
    }
}
