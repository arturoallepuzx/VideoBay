<?php

declare(strict_types=1);

namespace App\Streaming\Application\TranscodeVideo;

use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Event\VideoFileReady;
use App\Streaming\Domain\Exception\InvalidVideoFormatException;
use App\Streaming\Domain\Exception\TranscodingFailedException;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoTranscoderInterface;

class TranscodeVideo
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoTranscoderInterface $transcoder,
        private VideoFileStorageInterface $storage,
        private TransactionRunnerInterface $transactionRunner,
        private DomainEventDispatcherInterface $eventDispatcher,
        private bool $keepOriginalAfterProcessing,
    ) {}

    public function __invoke(string $videoFileUuid): void
    {
        $uuid = Uuid::create($videoFileUuid);

        $videoFile = $this->transactionRunner->run(function () use ($uuid): ?VideoFile {
            $videoFile = $this->videoFileRepository->findByUuidForUpdate($uuid);

            if ($videoFile === null || ! $videoFile->processingStatus()->isPending()) {
                return null;
            }

            $videoFile->markProcessing();
            $this->videoFileRepository->update($videoFile);

            return $videoFile;
        });

        if ($videoFile === null) {
            return;
        }

        $outputPath = $this->storage->absolutePathInRoot($uuid->value().'.mp4');
        $originalPath = (string) $videoFile->originalPath();

        try {
            $this->transcoder->transcodeToMp4($originalPath, $outputPath);
            $metadata = $this->transcoder->probe($outputPath);

            $videoFile->markReady(
                $outputPath,
                $metadata['duration_seconds'],
                $metadata['file_size_bytes'],
                $metadata['audio_language'],
            );

            $this->videoFileRepository->update($videoFile);
            $this->eventDispatcher->dispatch(VideoFileReady::create($videoFile->id(), $videoFile->movieId()));

            if (! $this->keepOriginalAfterProcessing) {
                $this->storage->delete($originalPath);
                $videoFile->clearOriginalReference();
                $this->videoFileRepository->update($videoFile);
            }
        } catch (TranscodingFailedException|InvalidVideoFormatException $e) {
            $videoFile->markFailed($e->getMessage());
            $this->videoFileRepository->update($videoFile);
        }
    }
}
