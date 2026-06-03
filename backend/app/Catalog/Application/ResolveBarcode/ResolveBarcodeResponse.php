<?php

declare(strict_types=1);

namespace App\Catalog\Application\ResolveBarcode;

use App\Catalog\Domain\Entity\Movie;
use App\Shared\Domain\ValueObject\BarcodeValue;

final readonly class ResolveBarcodeResponse
{
    private const SOURCE_LOCAL_CACHE = 'local_cache';

    private const SOURCE_TMDB_CANDIDATES = 'tmdb_candidates';

    private const SOURCE_EXTERNAL_TITLE = 'external_title_only';

    private const SOURCE_UNRESOLVED = 'unresolved';

    /**
     * @param  list<array<string, mixed>>  $candidates
     */
    private function __construct(
        public string $barcode,
        public string $source,
        public ?array $movie,
        public ?string $externalTitle,
        public array $candidates,
    ) {}

    public static function fromLocalHit(Movie $movie): self
    {
        return new self(
            barcode: '',
            source: self::SOURCE_LOCAL_CACHE,
            movie: [
                'uuid' => $movie->id()->value(),
                'tmdb_id' => $movie->tmdbId()?->value(),
                'title' => $movie->title()->value(),
                'poster_path' => $movie->posterPath()?->value(),
            ],
            externalTitle: null,
            candidates: [],
        );
    }

    /**
     * @param  array{results: list<array<string, mixed>>, page: int, total_pages: int, total_results: int}  $tmdbResult
     */
    public static function fromTmdbCandidates(BarcodeValue $barcode, string $externalTitle, array $tmdbResult): self
    {
        $candidates = array_map(
            fn (array $movie): array => [
                'tmdb_id' => isset($movie['id']) ? (int) $movie['id'] : null,
                'title' => isset($movie['title']) ? (string) $movie['title'] : '',
                'release_date' => isset($movie['release_date']) && $movie['release_date'] !== '' ? (string) $movie['release_date'] : null,
                'poster_path' => isset($movie['poster_path']) && $movie['poster_path'] !== '' ? (string) $movie['poster_path'] : null,
                'overview' => isset($movie['overview']) && $movie['overview'] !== '' ? (string) $movie['overview'] : null,
            ],
            $tmdbResult['results'],
        );

        return new self(
            barcode: $barcode->value(),
            source: self::SOURCE_TMDB_CANDIDATES,
            movie: null,
            externalTitle: $externalTitle,
            candidates: array_values($candidates),
        );
    }

    public static function externalTitleOnly(BarcodeValue $barcode, string $externalTitle): self
    {
        return new self(
            barcode: $barcode->value(),
            source: self::SOURCE_EXTERNAL_TITLE,
            movie: null,
            externalTitle: $externalTitle,
            candidates: [],
        );
    }

    public static function unresolved(BarcodeValue $barcode): self
    {
        return new self(
            barcode: $barcode->value(),
            source: self::SOURCE_UNRESOLVED,
            movie: null,
            externalTitle: null,
            candidates: [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'barcode' => $this->barcode,
            'source' => $this->source,
            'movie' => $this->movie,
            'external_title' => $this->externalTitle,
            'candidates' => $this->candidates,
        ];
    }
}
