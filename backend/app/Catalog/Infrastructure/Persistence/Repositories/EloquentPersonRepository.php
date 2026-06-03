<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Repositories;

use App\Catalog\Domain\Entity\Person;
use App\Catalog\Domain\Exception\PersonAlreadyExistsException;
use App\Catalog\Domain\Interfaces\PersonRepositoryInterface;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Catalog\Infrastructure\Persistence\Models\EloquentPerson;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MysqlUniqueConstraintViolationDetector;
use Illuminate\Database\QueryException;

class EloquentPersonRepository implements PersonRepositoryInterface
{
    private const UNIQUE_TMDB_CONSTRAINT = 'people_tmdb_active_unique';

    public function __construct(
        private EloquentPerson $model,
        private MysqlUniqueConstraintViolationDetector $uniqueConstraintViolationDetector,
    ) {}

    public function create(Person $person): void
    {
        try {
            $this->model->newQuery()->create([
                'uuid' => $person->id()->value(),
                'tmdb_id' => $person->tmdbId()?->value(),
                'name' => $person->name()->value(),
                'biography' => $person->biography()?->value(),
                'profile_path' => $person->profilePath()?->value(),
                'birthday' => $person->birthday()?->value(),
                'deathday' => $person->deathday()?->value(),
                'place_of_birth' => $person->placeOfBirth(),
                'cached_at' => $person->cachedAt()?->value(),
                'created_at' => $person->createdAt()->value(),
                'updated_at' => $person->updatedAt()->value(),
            ]);
        } catch (QueryException $e) {
            if ($person->tmdbId() !== null
                && $this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_TMDB_CONSTRAINT)) {
                throw PersonAlreadyExistsException::forTmdbId($person->tmdbId());
            }

            throw $e;
        }
    }

    public function update(Person $person): void
    {
        $this->model->newQuery()
            ->where('uuid', $person->id()->value())
            ->update([
                'tmdb_id' => $person->tmdbId()?->value(),
                'name' => $person->name()->value(),
                'biography' => $person->biography()?->value(),
                'profile_path' => $person->profilePath()?->value(),
                'birthday' => $person->birthday()?->value(),
                'deathday' => $person->deathday()?->value(),
                'place_of_birth' => $person->placeOfBirth(),
                'cached_at' => $person->cachedAt()?->value(),
                'updated_at' => $person->updatedAt()->value(),
            ]);
    }

    public function findByUuid(Uuid $uuid): ?Person
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByTmdbId(TmdbId $tmdbId): ?Person
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
            ->whereRaw('MATCH(name) AGAINST (? IN NATURAL LANGUAGE MODE)', [$query]);

        $total = (clone $base)->count();

        $results = $base
            ->orderByRaw('MATCH(name) AGAINST (? IN NATURAL LANGUAGE MODE) DESC', [$query])
            ->forPage($page, $perPage)
            ->get();

        return [
            'people' => $results->map(fn (EloquentPerson $p): Person => $this->toDomainEntity($p))->all(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    private function toDomainEntity(EloquentPerson $model): Person
    {
        return Person::fromPersistence(
            $model->uuid,
            $model->tmdb_id,
            $model->name,
            $model->biography,
            $model->profile_path,
            $model->birthday?->toDateTimeImmutable(),
            $model->deathday?->toDateTimeImmutable(),
            $model->place_of_birth,
            $model->cached_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
