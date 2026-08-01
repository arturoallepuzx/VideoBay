<?php

declare(strict_types=1);

namespace App\Streaming\Application\ServeVideoRange;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Exception\VideoFileNotReadyException;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\ValueObject\ByteRangeSpecification;

class ServeVideoRange
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoFileStorageInterface $storage,
    ) {}

    public function __invoke(string $videoFileUuid, ?ByteRangeSpecification $specification): ServeVideoRangeResponse
    {
        $uuid = Uuid::create($videoFileUuid);
        $videoFile = $this->videoFileRepository->findByUuid($uuid);

        if ($videoFile === null || $videoFile->isDeleted()) {
            throw VideoFileNotFoundException::forUuid($uuid);
        }

        if (! $videoFile->isReady()) {
            throw VideoFileNotReadyException::forUuid($uuid, $videoFile->processingStatus()->value());
        }

        $location = $videoFile->processedLocation();
        $totalSizeBytes = $videoFile->metadata()?->fileSizeBytes();

        if ($location === null || $totalSizeBytes === null) {
            throw VideoFileNotReadyException::forUuid($uuid, $videoFile->processingStatus()->value());
        }

        $byteRange = $specification?->resolveAgainst($totalSizeBytes);

        return ServeVideoRangeResponse::create(
            $this->storage->readChunks($location, $byteRange),
            $byteRange,
            $totalSizeBytes,
        );
    }
}
