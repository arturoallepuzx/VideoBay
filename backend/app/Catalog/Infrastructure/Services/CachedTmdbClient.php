<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Services;

use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class CachedTmdbClient implements TmdbClientInterface
{
    public function __construct(
        private TmdbClientInterface $inner,
        private CacheRepository $cache,
        private int $searchTtlSeconds,
        private int $detailTtlSeconds,
        private int $recommendationsTtlSeconds,
        private int $personTtlSeconds,
    ) {}

    public function searchMovies(string $query, int $page = 1): array
    {
        return $this->cache->remember(
            sprintf('tmdb:search:movies:%s:p%d', md5(mb_strtolower($query)), $page),
            $this->searchTtlSeconds,
            fn (): array => $this->inner->searchMovies($query, $page),
        );
    }

    public function getMovieDetail(int $tmdbId): array
    {
        return $this->cache->remember(
            sprintf('tmdb:movie:detail:%d', $tmdbId),
            $this->detailTtlSeconds,
            fn (): array => $this->inner->getMovieDetail($tmdbId),
        );
    }

    public function getMovieRecommendations(int $tmdbId, int $page = 1): array
    {
        return $this->cache->remember(
            sprintf('tmdb:movie:recommendations:%d:p%d', $tmdbId, $page),
            $this->recommendationsTtlSeconds,
            fn (): array => $this->inner->getMovieRecommendations($tmdbId, $page),
        );
    }

    public function searchPeople(string $query, int $page = 1): array
    {
        return $this->cache->remember(
            sprintf('tmdb:search:people:%s:p%d', md5(mb_strtolower($query)), $page),
            $this->searchTtlSeconds,
            fn (): array => $this->inner->searchPeople($query, $page),
        );
    }

    public function getPersonDetail(int $tmdbId): array
    {
        return $this->cache->remember(
            sprintf('tmdb:person:detail:%d', $tmdbId),
            $this->personTtlSeconds,
            fn (): array => $this->inner->getPersonDetail($tmdbId),
        );
    }

    public function getPersonMovieCredits(int $tmdbId): array
    {
        return $this->cache->remember(
            sprintf('tmdb:person:credits:%d', $tmdbId),
            $this->personTtlSeconds,
            fn (): array => $this->inner->getPersonMovieCredits($tmdbId),
        );
    }
}
