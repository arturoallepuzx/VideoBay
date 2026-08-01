<?php

declare(strict_types=1);

namespace App\Streaming\Application\RegisterExistingVideoFile;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Interfaces\MovieResolverForStreamingInterface;
use App\Streaming\Domain\Interfaces\VideoFileRegistrationJobDispatcherInterface;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoMetadataProbeInterface;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;

class RegisterExistingVideoFile
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoFileStorageInterface $storage,
        private VideoMetadataProbeInterface $metadataProbe,
        private MovieResolverForStreamingInterface $movieResolver,
        private TransactionRunnerInterface $transactionRunner,
        private VideoFileRegistrationJobDispatcherInterface $registrationJobDispatcher,
    ) {}

    public function __invoke(
        string $sourceFilename,
        ?string $movieUuid,
        ?int $tmdbId,
    ): RegisterExistingVideoFileResponse {
        $sourceLocation = $this->storage->locationForVideo($sourceFilename);

        if (! $this->storage->exists($sourceLocation)) {
            throw VideoFileNotFoundException::forSourcePath($sourceFilename);
        }

        $movieId = $movieUuid !== null
            ? $this->movieResolver->resolveByUuid(Uuid::create($movieUuid))
            : $this->movieResolver->resolveByTmdbId((int) $tmdbId);

        $this->metadataProbe->probe($sourceLocation);

        $id = Uuid::generate();

        $videoFile = $this->transactionRunner->run(function () use ($id, $movieId, $sourceLocation): VideoFile {
            $videoFile = VideoFile::dddCreate(
                $id,
                $movieId,
                null,
                null,
                $sourceLocation,
                null,
                null,
                VideoFileProcessingStatus::processing(),
            );

            $this->videoFileRepository->create($videoFile);

            return $videoFile;
        });

        $this->registrationJobDispatcher->dispatch($videoFile->id());

        return RegisterExistingVideoFileResponse::create($videoFile);
    }
}
