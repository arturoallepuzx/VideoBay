<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MovieIdResolverInterface;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use App\Subtitle\Domain\Entity\Subtitle;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;
use App\Subtitle\Infrastructure\Persistence\Models\EloquentSubtitle;

class EloquentSubtitleRepository implements SubtitleRepositoryInterface
{
    public function __construct(
        private EloquentSubtitle $model,
        private MovieIdResolverInterface $movieIdResolver,
        private UserIdResolverInterface $userIdResolver,
    ) {}

    public function create(Subtitle $subtitle): void
    {
        $this->model->newQuery()->create([
            'uuid' => $subtitle->id()->value(),
            'movie_id' => $this->movieIdResolver->toInternalId($subtitle->movieId()),
            'language' => $subtitle->language()->value(),
            'label' => $subtitle->label()->value(),
            'source' => $subtitle->source()->value(),
            'provider' => $subtitle->provider(),
            'external_id' => $subtitle->externalId(),
            'file_path' => $subtitle->filePath(),
            'original_format' => $subtitle->originalFormat()->value(),
            'uploaded_by_user_id' => $subtitle->uploadedByUserId() !== null
                ? $this->userIdResolver->toInternalId($subtitle->uploadedByUserId())
                : null,
            'created_at' => $subtitle->createdAt()->value(),
            'updated_at' => $subtitle->updatedAt()->value(),
        ]);
    }

    public function update(Subtitle $subtitle): void
    {
        $this->model->newQueryWithoutScopes()
            ->where('uuid', $subtitle->id()->value())
            ->update([
                'language' => $subtitle->language()->value(),
                'label' => $subtitle->label()->value(),
                'file_path' => $subtitle->filePath(),
                'deleted_at' => $subtitle->deletedAt()?->value(),
                'updated_at' => $subtitle->updatedAt()->value(),
            ]);
    }

    public function findByUuid(Uuid $uuid): ?Subtitle
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByUuidForUpdate(Uuid $uuid): ?Subtitle
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->lockForUpdate()
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByProviderAndExternalId(string $provider, string $externalId): ?Subtitle
    {
        $model = $this->model->newQueryWithoutScopes()
            ->where('provider', strtolower(trim($provider)))
            ->where('external_id', trim($externalId))
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findManyByUuids(array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        $uuidValues = array_map(fn (Uuid $uuid): string => $uuid->value(), $uuids);
        $models = $this->model->newQueryWithoutScopes()
            ->whereIn('uuid', $uuidValues)
            ->get();

        $subtitles = [];
        foreach ($models as $model) {
            $subtitle = $this->toDomainEntity($model);
            $subtitles[$subtitle->id()->value()] = $subtitle;
        }

        return $subtitles;
    }

    public function listByMovie(Uuid $movieId): array
    {
        $models = $this->model->newQuery()
            ->where('movie_id', $this->movieIdResolver->toInternalId($movieId))
            ->orderBy('language')
            ->orderBy('label')
            ->get();

        return $models->map(fn (EloquentSubtitle $model): Subtitle => $this->toDomainEntity($model))->all();
    }

    private function toDomainEntity(EloquentSubtitle $model): Subtitle
    {
        $movieUuid = $this->movieIdResolver->toDomainUuid((int) $model->movie_id);
        $userUuid = $model->uploaded_by_user_id !== null
            ? $this->userIdResolver->toDomainUuid((int) $model->uploaded_by_user_id)
            : null;

        return Subtitle::fromPersistence(
            (string) $model->uuid,
            $movieUuid->value(),
            (string) $model->language,
            (string) $model->label,
            (string) $model->source,
            $model->provider !== null ? (string) $model->provider : null,
            $model->external_id !== null ? (string) $model->external_id : null,
            (string) $model->file_path,
            (string) $model->original_format,
            $userUuid?->value(),
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
