<?php

declare(strict_types=1);

namespace App\Catalog\Application\SearchPeople;

use App\Catalog\Domain\Entity\Person;

final readonly class SearchPeopleResponse
{
    /** @param list<array<string, mixed>> $results */
    private function __construct(
        public array $results,
        public int $page,
        public int $totalPages,
        public int $totalResults,
    ) {}

    public static function empty(int $page): self
    {
        return new self([], $page, 0, 0);
    }

    /**
     * @param  array{results: list<array<string, mixed>>, page: int, total_pages: int, total_results: int}  $tmdbResult
     */
    public static function fromTmdb(array $tmdbResult): self
    {
        $results = array_map(
            fn (array $person): array => [
                'tmdb_id' => isset($person['id']) ? (int) $person['id'] : null,
                'name' => isset($person['name']) ? (string) $person['name'] : '',
                'profile_path' => isset($person['profile_path']) && $person['profile_path'] !== '' ? (string) $person['profile_path'] : null,
                'known_for_department' => isset($person['known_for_department']) ? (string) $person['known_for_department'] : null,
            ],
            $tmdbResult['results'],
        );

        return new self(
            results: array_values($results),
            page: $tmdbResult['page'],
            totalPages: $tmdbResult['total_pages'],
            totalResults: $tmdbResult['total_results'],
        );
    }

    /**
     * @param  array{people: list<Person>, total: int, page: int, totalPages: int}  $fallback
     */
    public static function fromLocalFallback(array $fallback): self
    {
        $results = array_map(
            fn (Person $person): array => [
                'tmdb_id' => $person->tmdbId()?->value(),
                'name' => $person->name()->value(),
                'profile_path' => $person->profilePath()?->value(),
                'known_for_department' => null,
            ],
            $fallback['people'],
        );

        return new self(
            results: array_values($results),
            page: $fallback['page'],
            totalPages: $fallback['totalPages'],
            totalResults: $fallback['total'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'results' => $this->results,
            'page' => $this->page,
            'total_pages' => $this->totalPages,
            'total_results' => $this->totalResults,
        ];
    }
}
