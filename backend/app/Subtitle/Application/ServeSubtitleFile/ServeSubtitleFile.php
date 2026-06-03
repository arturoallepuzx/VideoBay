<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ServeSubtitleFile;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Exception\SubtitleFileNotFoundException;
use App\Subtitle\Domain\Exception\SubtitleNotFoundException;
use App\Subtitle\Domain\Interfaces\SubtitleFileStorageInterface;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;

class ServeSubtitleFile
{
    public function __construct(
        private SubtitleRepositoryInterface $subtitleRepository,
        private SubtitleFileStorageInterface $storage,
    ) {}

    public function __invoke(string $subtitleUuid): ServeSubtitleFileResponse
    {
        $uuid = Uuid::create($subtitleUuid);
        $subtitle = $this->subtitleRepository->findByUuid($uuid);

        if ($subtitle === null || $subtitle->isDeleted()) {
            throw SubtitleNotFoundException::forUuid($uuid);
        }

        if (! $this->storage->exists($subtitle->filePath())) {
            throw SubtitleFileNotFoundException::forPath($subtitle->filePath());
        }

        return ServeSubtitleFileResponse::create($this->storage->absolutePath($subtitle->filePath()));
    }
}
