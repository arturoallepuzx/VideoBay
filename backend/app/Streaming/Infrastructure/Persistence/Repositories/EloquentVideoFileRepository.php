<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MovieIdResolverInterface;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;
use App\Streaming\Infrastructure\Persistence\Models\EloquentVideoFile;

class EloquentVideoFileRepository implements VideoFileRepositoryInterface
{
    public function __construct(
        private EloquentVideoFile $model,
        private MovieIdResolverInterface $movieIdResolver,
    ) {}

    public function findByUuid(Uuid $uuid): ?VideoFile
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByUuidForUpdate(Uuid $uuid): ?VideoFile
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->lockForUpdate()
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function create(VideoFile $videoFile): void
    {
        $this->model->newQuery()->create([
            'uuid' => $videoFile->id()->value(),
            'movie_id' => $this->movieIdResolver->toInternalId($videoFile->movieId()),
            'original_filename' => $videoFile->originalFilename(),
            'original_format' => $videoFile->originalFormat(),
            'original_path' => $videoFile->originalPath(),
            'processed_path' => $videoFile->processedPath(),
            'mime_type' => 'video/mp4',
            'duration_seconds' => $videoFile->durationSeconds(),
            'file_size_bytes' => $videoFile->fileSizeBytes(),
            'audio_language' => $videoFile->audioLanguage(),
            'processing_status' => $videoFile->processingStatus()->value(),
            'processing_error' => $videoFile->processingError(),
            'created_at' => $videoFile->createdAt()->value(),
            'updated_at' => $videoFile->updatedAt()->value(),
        ]);
    }

    public function update(VideoFile $videoFile): void
    {
        $this->model->newQuery()
            ->where('uuid', $videoFile->id()->value())
            ->update([
                'movie_id' => $this->movieIdResolver->toInternalId($videoFile->movieId()),
                'original_path' => $videoFile->originalPath(),
                'processed_path' => $videoFile->processedPath(),
                'duration_seconds' => $videoFile->durationSeconds(),
                'file_size_bytes' => $videoFile->fileSizeBytes(),
                'audio_language' => $videoFile->audioLanguage(),
                'processing_status' => $videoFile->processingStatus()->value(),
                'processing_error' => $videoFile->processingError(),
                'deleted_at' => $videoFile->deletedAt()?->value(),
                'updated_at' => $videoFile->updatedAt()->value(),
            ]);
    }

    public function listAllProcessedPaths(): array
    {
        return $this->model->newQuery()
            ->whereNotNull('processed_path')
            ->pluck('processed_path')
            ->all();
    }

    public function listAllOriginalPaths(): array
    {
        return $this->model->newQuery()
            ->whereNotNull('original_path')
            ->pluck('original_path')
            ->all();
    }

    public function listForAdmin(int $page, int $perPage): array
    {
        $base = $this->model->newQueryWithoutScopes()
            ->whereNull('video_files.deleted_at')
            ->join('movies as m', 'video_files.movie_id', '=', 'm.id');

        $total = (int) (clone $base)->count();

        $rows = $base
            ->orderByDesc('video_files.created_at')
            ->forPage($page, $perPage)
            ->get(['video_files.*', 'm.uuid as movie_uuid', 'm.title as movie_title']);

        $items = $rows->map(fn (EloquentVideoFile $m): VideoFile => $this->toDomainEntity($m))->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => $total > 0 ? (int) ceil($total / max(1, $perPage)) : 0,
        ];
    }

    public function findFailedOlderThan(DomainDateTime $threshold): array
    {
        return $this->model->newQuery()
            ->where('processing_status', VideoFileProcessingStatus::failed()->value())
            ->where('updated_at', '<', $threshold->value())
            ->get()
            ->map(fn (EloquentVideoFile $m): VideoFile => $this->toDomainEntity($m))
            ->all();
    }

    private function toDomainEntity(EloquentVideoFile $model): VideoFile
    {
        return VideoFile::fromPersistence(
            (string) $model->uuid,
            $this->resolveMovieUuid($model),
            $model->original_filename !== null ? (string) $model->original_filename : null,
            $model->original_format !== null ? (string) $model->original_format : null,
            $model->original_path !== null ? (string) $model->original_path : null,
            $model->processed_path !== null ? (string) $model->processed_path : null,
            $model->duration_seconds !== null ? (int) $model->duration_seconds : null,
            $model->file_size_bytes !== null ? (int) $model->file_size_bytes : null,
            $model->audio_language !== null ? (string) $model->audio_language : null,
            (string) $model->processing_status,
            $model->processing_error !== null ? (string) $model->processing_error : null,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }

    private function resolveMovieUuid(EloquentVideoFile $model): string
    {
        $uuid = $model->getAttribute('movie_uuid');
        if ($uuid !== null) {
            return (string) $uuid;
        }

        return (string) $this->movieIdResolver->toDomainUuid((int) $model->movie_id)->value();
    }
}
