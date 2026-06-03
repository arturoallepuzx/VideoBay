<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Repositories;

use App\Catalog\Domain\Entity\MovieCredit;
use App\Catalog\Domain\Interfaces\MovieCreditRepositoryInterface;
use App\Catalog\Infrastructure\Persistence\Models\EloquentMovie;
use App\Catalog\Infrastructure\Persistence\Models\EloquentMovieCredit;
use App\Catalog\Infrastructure\Persistence\Models\EloquentPerson;
use App\Shared\Domain\ValueObject\Uuid;

class EloquentMovieCreditRepository implements MovieCreditRepositoryInterface
{
    public function __construct(
        private EloquentMovieCredit $model,
    ) {}

    public function create(MovieCredit $credit): void
    {
        $this->model->newQuery()->create([
            'movie_id' => $this->resolveMovieInternalId($credit->movieId()),
            'person_id' => $this->resolvePersonInternalId($credit->personId()),
            'department' => $credit->department()->value(),
            'job' => $credit->job(),
            'character_name' => $credit->characterName(),
            'credit_order' => $credit->creditOrder(),
        ]);
    }

    public function deleteByMovieUuid(Uuid $movieUuid): void
    {
        $movieId = $this->resolveMovieInternalId($movieUuid);

        $this->model->newQuery()
            ->where('movie_id', $movieId)
            ->delete();
    }

    public function findByMovieUuid(Uuid $movieUuid): array
    {
        $models = $this->model->newQuery()
            ->join('movies', 'movie_credits.movie_id', '=', 'movies.id')
            ->join('people', 'movie_credits.person_id', '=', 'people.id')
            ->where('movies.uuid', $movieUuid->value())
            ->orderByRaw('movie_credits.credit_order IS NULL, movie_credits.credit_order ASC')
            ->select([
                'movie_credits.*',
                'movies.uuid as movie_uuid',
                'people.uuid as person_uuid',
            ])
            ->get();

        return $models->map(fn (EloquentMovieCredit $m): MovieCredit => $this->toDomainEntity($m))->all();
    }

    public function findByPersonUuid(Uuid $personUuid): array
    {
        $models = $this->model->newQuery()
            ->join('movies', 'movie_credits.movie_id', '=', 'movies.id')
            ->join('people', 'movie_credits.person_id', '=', 'people.id')
            ->where('people.uuid', $personUuid->value())
            ->orderByRaw('movie_credits.credit_order IS NULL, movie_credits.credit_order ASC')
            ->select([
                'movie_credits.*',
                'movies.uuid as movie_uuid',
                'people.uuid as person_uuid',
            ])
            ->get();

        return $models->map(fn (EloquentMovieCredit $m): MovieCredit => $this->toDomainEntity($m))->all();
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

    private function resolvePersonInternalId(Uuid $personUuid): int
    {
        $id = EloquentPerson::query()
            ->where('uuid', $personUuid->value())
            ->value('id');

        if ($id === null) {
            throw new \RuntimeException(sprintf('Person "%s" not found in database.', $personUuid->value()));
        }

        return (int) $id;
    }

    private function toDomainEntity(EloquentMovieCredit $model): MovieCredit
    {
        return MovieCredit::fromPersistence(
            (int) $model->id,
            (string) $model->getAttribute('movie_uuid'),
            (string) $model->getAttribute('person_uuid'),
            (string) $model->department,
            $model->job !== null ? (string) $model->job : null,
            $model->character_name !== null ? (string) $model->character_name : null,
            $model->credit_order !== null ? (int) $model->credit_order : null,
        );
    }
}
