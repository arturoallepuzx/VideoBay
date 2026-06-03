<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;

interface VideoFileRepositoryInterface
{
    public function findByUuid(Uuid $uuid): ?VideoFile;

    public function findByUuidForUpdate(Uuid $uuid): ?VideoFile;

    public function create(VideoFile $videoFile): void;

    public function update(VideoFile $videoFile): void;

    /** @return list<string> */
    public function listAllProcessedPaths(): array;

    /** @return list<string> */
    public function listAllOriginalPaths(): array;

    /** @return array{items: list<VideoFile>, total: int, page: int, totalPages: int} */
    public function listForAdmin(int $page, int $perPage): array;

    /** @return list<VideoFile> */
    public function findFailedOlderThan(DomainDateTime $threshold): array;
}
