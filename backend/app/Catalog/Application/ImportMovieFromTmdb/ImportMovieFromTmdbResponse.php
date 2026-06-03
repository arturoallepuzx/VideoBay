<?php

declare(strict_types=1);

namespace App\Catalog\Application\ImportMovieFromTmdb;

use App\Catalog\Domain\Entity\Movie;

final readonly class ImportMovieFromTmdbResponse
{
    private function __construct(
        public string $uuid,
        public ?int $tmdbId,
        public string $title,
    ) {}

    public static function create(Movie $movie): self
    {
        return new self(
            uuid: $movie->id()->value(),
            tmdbId: $movie->tmdbId()?->value(),
            title: $movie->title()->value(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'tmdb_id' => $this->tmdbId,
            'title' => $this->title,
        ];
    }
}
