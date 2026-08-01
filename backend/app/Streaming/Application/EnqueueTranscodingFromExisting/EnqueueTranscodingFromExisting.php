<?php

declare(strict_types=1);

namespace App\Streaming\Application\EnqueueTranscodingFromExisting;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Interfaces\MovieResolverForStreamingInterface;
use App\Streaming\Domain\Interfaces\TranscodingJobDispatcherInterface;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoTranscodeStorageInterface;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;

class EnqueueTranscodingFromExisting
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoTranscodeStorageInterface $storage,
        private MovieResolverForStreamingInterface $movieResolver,
        private TranscodingJobDispatcherInterface $transcodingJobDispatcher,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(
        string $sourceFilename,
        ?string $movieUuid,
        ?int $tmdbId,
    ): EnqueueTranscodingFromExistingResponse {
        $sourceLocation = $this->storage->locationForOriginal($sourceFilename);

        if (! $this->storage->exists($sourceLocation)) {
            throw VideoFileNotFoundException::forSourcePath($sourceFilename);
        }

        $movieId = $movieUuid !== null
            ? $this->movieResolver->resolveByUuid(Uuid::create($movieUuid))
            : $this->movieResolver->resolveByTmdbId((int) $tmdbId);

        $id = Uuid::generate();
        $ext = strtolower(pathinfo($sourceFilename, PATHINFO_EXTENSION));
        $originalLocation = $this->storage->locationForOriginal($id->value().'.'.$ext);

        $videoFile = $this->transactionRunner->run(function () use (
            $id,
            $movieId,
            $sourceFilename,
            $ext,
            $sourceLocation,
            $originalLocation,
        ): VideoFile {
            $videoFile = VideoFile::dddCreate(
                $id,
                $movieId,
                $sourceFilename,
                $ext,
                $originalLocation,
                null,
                null,
                null,
                null,
                VideoFileProcessingStatus::pending(),
            );

            $this->videoFileRepository->create($videoFile);
            $this->storage->rename($sourceLocation, $originalLocation);

            return $videoFile;
        });

        $this->transcodingJobDispatcher->dispatch($videoFile->id());

        return EnqueueTranscodingFromExistingResponse::create($videoFile);
    }
}
