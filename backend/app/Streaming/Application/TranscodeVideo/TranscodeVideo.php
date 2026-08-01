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
use App\Streaming\Domain\Interfaces\VideoMetadataProbeInterface;
use App\Streaming\Domain\Interfaces\VideoTranscoderInterface;
use App\Streaming\Domain\Interfaces\VideoTranscodeStorageInterface;

class TranscodeVideo
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoTranscoderInterface $transcoder,
        private VideoMetadataProbeInterface $metadataProbe,
        private VideoTranscodeStorageInterface $storage,
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

        $outputLocation = $this->storage->locationForVideo($uuid->value().'.mp4');
        $originalLocation = $videoFile->originalLocation();

        try {
            $this->transcoder->transcodeToMp4((string) $originalLocation?->key(), $outputLocation->key());
            $metadata = $this->metadataProbe->probe($outputLocation);

            $videoFile->markReady($outputLocation, $metadata);

            $this->videoFileRepository->update($videoFile);
            $this->eventDispatcher->dispatch(VideoFileReady::create($videoFile->id(), $videoFile->movieId()));

            if (! $this->keepOriginalAfterProcessing && $originalLocation !== null) {
                $this->storage->delete($originalLocation);
                $videoFile->clearOriginalReference();
                $this->videoFileRepository->update($videoFile);
            }
        } catch (TranscodingFailedException|InvalidVideoFormatException $e) {
            $videoFile->markFailed($e->getMessage());
            $this->videoFileRepository->update($videoFile);
        }
    }
}
