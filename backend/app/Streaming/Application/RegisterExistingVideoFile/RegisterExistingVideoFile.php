<?php

declare(strict_types=1);

namespace App\Streaming\Application\RegisterExistingVideoFile;

use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Event\VideoFileReady;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Interfaces\MovieResolverForStreamingInterface;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoTranscoderInterface;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;

class RegisterExistingVideoFile
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoFileStorageInterface $storage,
        private VideoTranscoderInterface $transcoder,
        private MovieResolverForStreamingInterface $movieResolver,
        private TransactionRunnerInterface $transactionRunner,
        private DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(
        string $sourceFilename,
        ?string $movieUuid,
        ?int $tmdbId,
    ): RegisterExistingVideoFileResponse {
        if (! $this->storage->existsInRoot($sourceFilename)) {
            throw VideoFileNotFoundException::forSourcePath($sourceFilename);
        }

        $movieId = $movieUuid !== null
            ? $this->movieResolver->resolveByUuid(Uuid::create($movieUuid))
            : $this->movieResolver->resolveByTmdbId((int) $tmdbId);

        $sourcePath = $this->storage->absolutePathInRoot($sourceFilename);
        $metadata = $this->transcoder->probe($sourcePath);

        $id = Uuid::generate();
        $targetPath = $this->storage->absolutePathInRoot($id->value().'.mp4');

        $videoFile = $this->transactionRunner->run(function () use (
            $id,
            $movieId,
            $metadata,
            $sourcePath,
            $targetPath,
        ): VideoFile {
            $videoFile = VideoFile::dddCreate(
                $id,
                $movieId,
                null,
                null,
                null,
                $targetPath,
                $metadata['duration_seconds'],
                $metadata['file_size_bytes'],
                $metadata['audio_language'],
                VideoFileProcessingStatus::ready(),
            );

            $this->videoFileRepository->create($videoFile);
            $this->storage->rename($sourcePath, $targetPath);
            $this->eventDispatcher->dispatch(VideoFileReady::create($videoFile->id(), $videoFile->movieId()));

            return $videoFile;
        });

        return RegisterExistingVideoFileResponse::create($videoFile);
    }
}
