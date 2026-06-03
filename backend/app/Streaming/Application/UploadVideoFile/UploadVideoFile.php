<?php

declare(strict_types=1);

namespace App\Streaming\Application\UploadVideoFile;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Interfaces\MovieResolverForStreamingInterface;
use App\Streaming\Domain\Interfaces\TranscodingJobDispatcherInterface;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;

class UploadVideoFile
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoFileStorageInterface $storage,
        private MovieResolverForStreamingInterface $movieResolver,
        private TranscodingJobDispatcherInterface $transcodingJobDispatcher,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(
        string $temporaryUploadedPath,
        string $originalFilename,
        ?string $movieUuid,
        ?int $tmdbId,
    ): UploadVideoFileResponse {
        $movieId = $movieUuid !== null
            ? $this->movieResolver->resolveByUuid(Uuid::create($movieUuid))
            : $this->movieResolver->resolveByTmdbId((int) $tmdbId);

        $id = Uuid::generate();
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $targetPath = $this->storage->absolutePathInOriginals($id->value().'.'.$ext);

        $videoFile = $this->transactionRunner->run(function () use (
            $id,
            $movieId,
            $originalFilename,
            $ext,
            $temporaryUploadedPath,
            $targetPath,
        ): VideoFile {
            $videoFile = VideoFile::dddCreate(
                $id,
                $movieId,
                $originalFilename,
                $ext,
                $targetPath,
                null,
                null,
                null,
                null,
                VideoFileProcessingStatus::pending(),
            );

            $this->videoFileRepository->create($videoFile);
            $this->storage->moveUploadedTo($temporaryUploadedPath, $targetPath);

            return $videoFile;
        });

        $this->transcodingJobDispatcher->dispatch($videoFile->id());

        return UploadVideoFileResponse::create($videoFile);
    }
}
