<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Interfaces;

use App\Catalog\Domain\Exception\TmdbServiceUnavailableException;

interface TmdbClientInterface
{
    /**
     * @return array{results: list<array<string, mixed>>, page: int, total_pages: int, total_results: int}
     *
     * @throws TmdbServiceUnavailableException
     */
    public function searchMovies(string $query, int $page = 1): array;

    /**
     * @return array<string, mixed>
     *
     * @throws TmdbServiceUnavailableException
     */
    public function getMovieDetail(int $tmdbId): array;

    /**
     * @return array{results: list<array<string, mixed>>, page: int, total_pages: int, total_results: int}
     *
     * @throws TmdbServiceUnavailableException
     */
    public function getMovieRecommendations(int $tmdbId, int $page = 1): array;

    /**
     * @return array{results: list<array<string, mixed>>, page: int, total_pages: int, total_results: int}
     *
     * @throws TmdbServiceUnavailableException
     */
    public function searchPeople(string $query, int $page = 1): array;

    /**
     * @return array<string, mixed>
     *
     * @throws TmdbServiceUnavailableException
     */
    public function getPersonDetail(int $tmdbId): array;

    /**
     * @return array{cast: list<array<string, mixed>>, crew: list<array<string, mixed>>}
     *
     * @throws TmdbServiceUnavailableException
     */
    public function getPersonMovieCredits(int $tmdbId): array;
}
