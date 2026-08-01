<?php

declare(strict_types=1);

namespace App\Streaming\Application\ListPendingVideoFiles;

use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoTranscodeStorageInterface;
use App\Streaming\Domain\ValueObject\StorageDisk;

class ListPendingVideoFiles
{
    public function __construct(
        private VideoFileStorageInterface $storage,
        private VideoTranscodeStorageInterface $transcodeStorage,
        private StorageDisk $ingestionDisk,
    ) {}

    public function __invoke(): ListPendingVideoFilesResponse
    {
        return ListPendingVideoFilesResponse::create(
            $this->storage->listVideos(),
            $this->transcodeStorage->listOriginals(),
            $this->ingestionDisk,
        );
    }
}
