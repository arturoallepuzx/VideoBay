<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Repositories;

use App\Catalog\Domain\Entity\Genre;
use App\Catalog\Domain\Interfaces\GenreRepositoryInterface;
use App\Catalog\Domain\ValueObject\GenreName;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Catalog\Infrastructure\Persistence\Models\EloquentGenre;
use App\Catalog\Infrastructure\Persistence\Models\EloquentMovie;
use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Support\Facades\DB;

class EloquentGenreRepository implements GenreRepositoryInterface
{
    public function __construct(
        private EloquentGenre $model,
    ) {}

    public function findByTmdbId(TmdbId $tmdbId): ?Genre
    {
        $model = $this->model->newQuery()
            ->where('tmdb_id', $tmdbId->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findOrCreate(TmdbId $tmdbId, GenreName $name): Genre
    {
        $model = $this->model->newQuery()->firstOrCreate(
            ['tmdb_id' => $tmdbId->value()],
            ['name' => $name->value()],
        );

        return $this->toDomainEntity($model);
    }

    public function attachToMovie(Uuid $movieUuid, Genre $genre): void
    {
        $movieId = $this->resolveMovieInternalId($movieUuid);
        $genreId = $genre->id();

        if ($genreId === null) {
            throw new \LogicException('Cannot attach a genre without persisted id.');
        }

        DB::table('movie_genre')
            ->insertOrIgnore([
                'movie_id' => $movieId,
                'genre_id' => $genreId,
            ]);
    }

    public function detachAllFromMovie(Uuid $movieUuid): void
    {
        $movieId = $this->resolveMovieInternalId($movieUuid);

        DB::table('movie_genre')
            ->where('movie_id', $movieId)
            ->delete();
    }

    public function findByMovieUuid(Uuid $movieUuid): array
    {
        $movieId = $this->resolveMovieInternalId($movieUuid);

        return $this->model->newQuery()
            ->join('movie_genre', 'genres.id', '=', 'movie_genre.genre_id')
            ->where('movie_genre.movie_id', $movieId)
            ->orderBy('genres.name')
            ->select('genres.*')
            ->get()
            ->map(fn (EloquentGenre $g): Genre => $this->toDomainEntity($g))
            ->all();
    }

    private function resolveMovieInternalId(Uuid $movieUuid): int
    {
        $id = EloquentMovie::query()
            ->where('uuid', $movieUuid->value())
            ->value('id');

        if ($id === null) {
            throw new \RuntimeException(sprintf('Movie "%s" not found in database.', $movieUuid->value()));
        }

        return (int) $id;
    }

    private function toDomainEntity(EloquentGenre $model): Genre
    {
        return Genre::fromPersistence(
            (int) $model->id,
            $model->tmdb_id,
            $model->name,
        );
    }
}
