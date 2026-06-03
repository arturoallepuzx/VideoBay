<?php

declare(strict_types=1);

namespace App\Streaming\Application\ServeVideoRange;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Exception\VideoFileNotReadyException;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;

class ServeVideoRange
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
    ) {}

    public function __invoke(string $videoFileUuid): ServeVideoRangeResponse
    {
        $uuid = Uuid::create($videoFileUuid);
        $videoFile = $this->videoFileRepository->findByUuid($uuid);

        if ($videoFile === null || $videoFile->isDeleted()) {
            throw VideoFileNotFoundException::forUuid($uuid);
        }

        if (! $videoFile->isReady()) {
            throw VideoFileNotReadyException::forUuid($uuid, $videoFile->processingStatus()->value());
        }

        return ServeVideoRangeResponse::create($videoFile);
    }
}
