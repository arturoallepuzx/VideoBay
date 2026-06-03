<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListPersonFilmography;

final readonly class ListPersonFilmographyResponse
{
    /**
     * @param  list<array<string, mixed>>  $cast
     * @param  list<array<string, mixed>>  $crew
     */
    private function __construct(
        public array $cast,
        public array $crew,
    ) {}

    /**
     * @param  array{cast: list<array<string, mixed>>, crew: list<array<string, mixed>>}  $tmdbCredits
     */
    public static function create(array $tmdbCredits): self
    {
        $mapMovie = static fn (array $entry): array => [
            'tmdb_id' => isset($entry['id']) ? (int) $entry['id'] : null,
            'title' => isset($entry['title']) ? (string) $entry['title'] : '',
            'release_date' => isset($entry['release_date']) && $entry['release_date'] !== '' ? (string) $entry['release_date'] : null,
            'poster_path' => isset($entry['poster_path']) && $entry['poster_path'] !== '' ? (string) $entry['poster_path'] : null,
            'character_name' => isset($entry['character']) && $entry['character'] !== '' ? (string) $entry['character'] : null,
            'job' => isset($entry['job']) && $entry['job'] !== '' ? (string) $entry['job'] : null,
            'department' => isset($entry['department']) ? (string) $entry['department'] : null,
        ];

        return new self(
            cast: array_values(array_map($mapMovie, $tmdbCredits['cast'])),
            crew: array_values(array_map($mapMovie, $tmdbCredits['crew'])),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'cast' => $this->cast,
            'crew' => $this->crew,
        ];
    }
}
