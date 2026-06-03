<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Repositories;

use App\Catalog\Domain\Entity\Movie;
use App\Catalog\Domain\Exception\MovieAlreadyExistsException;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Catalog\Infrastructure\Persistence\Models\EloquentMovie;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MysqlUniqueConstraintViolationDetector;
use Illuminate\Database\QueryException;

class EloquentMovieRepository implements MovieRepositoryInterface
{
    private const UNIQUE_TMDB_CONSTRAINT = 'movies_tmdb_active_unique';

    public function __construct(
        private EloquentMovie $model,
        private MysqlUniqueConstraintViolationDetector $uniqueConstraintViolationDetector,
    ) {}

    public function create(Movie $movie): void
    {
        try {
            $this->model->newQuery()->create([
                'uuid' => $movie->id()->value(),
                'tmdb_id' => $movie->tmdbId()?->value(),
                'imdb_id' => $movie->imdbId()?->value(),
                'title' => $movie->title()->value(),
                'original_title' => $movie->originalTitle()?->value(),
                'overview' => $movie->overview()?->value(),
                'release_date' => $movie->releaseDate()?->value(),
                'runtime_minutes' => $movie->runtimeMinutes()?->value(),
                'original_language' => $movie->originalLanguage()?->value(),
                'poster_path' => $movie->posterPath()?->value(),
                'backdrop_path' => $movie->backdropPath()?->value(),
                'tmdb_rating' => $movie->tmdbRating()?->value(),
                'cached_at' => $movie->cachedAt()?->value(),
                'created_at' => $movie->createdAt()->value(),
                'updated_at' => $movie->updatedAt()->value(),
            ]);
        } catch (QueryException $e) {
            if ($movie->tmdbId() !== null
                && $this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_TMDB_CONSTRAINT)) {
                throw MovieAlreadyExistsException::forTmdbId($movie->tmdbId());
            }

            throw $e;
        }
    }

    public function update(Movie $movie): void
    {
        $this->model->newQuery()
            ->where('uuid', $movie->id()->value())
            ->update([
                'tmdb_id' => $movie->tmdbId()?->value(),
                'imdb_id' => $movie->imdbId()?->value(),
                'title' => $movie->title()->value(),
                'original_title' => $movie->originalTitle()?->value(),
                'overview' => $movie->overview()?->value(),
                'release_date' => $movie->releaseDate()?->value(),
                'runtime_minutes' => $movie->runtimeMinutes()?->value(),
                'original_language' => $movie->originalLanguage()?->value(),
                'poster_path' => $movie->posterPath()?->value(),
                'backdrop_path' => $movie->backdropPath()?->value(),
                'tmdb_rating' => $movie->tmdbRating()?->value(),
                'cached_at' => $movie->cachedAt()?->value(),
                'updated_at' => $movie->updatedAt()->value(),
            ]);
    }

    public function findByUuid(Uuid $uuid): ?Movie
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByTmdbId(TmdbId $tmdbId): ?Movie
    {
        $model = $this->model->newQuery()
            ->where('tmdb_id', $tmdbId->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function existsByTmdbId(TmdbId $tmdbId): bool
    {
        return $this->model->newQuery()
            ->where('tmdb_id', $tmdbId->value())
            ->exists();
    }

    public function findManyByUuids(array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        $uuidValues = array_map(fn (Uuid $u): string => $u->value(), $uuids);

        $models = $this->model->newQuery()
            ->whereIn('uuid', $uuidValues)
            ->get();

        $byUuid = [];
        foreach ($models as $model) {
            $entity = $this->toDomainEntity($model);
            $byUuid[$entity->id()->value()] = $entity;
        }

        return $byUuid;
    }

    public function searchByFulltext(string $query, int $page, int $perPage): array
    {
        $base = $this->model->newQuery()
            ->whereRaw(
                'MATCH(title, original_title, overview) AGAINST (? IN NATURAL LANGUAGE MODE)',
                [$query],
            );

        $total = (clone $base)->count();

        $results = $base
            ->orderByRaw(
                'MATCH(title, original_title, overview) AGAINST (? IN NATURAL LANGUAGE MODE) DESC',
                [$query],
            )
            ->forPage($page, $perPage)
            ->get();

        return [
            'movies' => $results->map(fn (EloquentMovie $m): Movie => $this->toDomainEntity($m))->all(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    private function toDomainEntity(EloquentMovie $model): Movie
    {
        return Movie::fromPersistence(
            $model->uuid,
            $model->tmdb_id,
            $model->imdb_id,
            $model->title,
            $model->original_title,
            $model->overview,
            $model->release_date?->toDateTimeImmutable(),
            $model->runtime_minutes,
            $model->original_language,
            $model->poster_path,
            $model->backdrop_path,
            $model->tmdb_rating !== null ? (float) $model->tmdb_rating : null,
            $model->cached_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
