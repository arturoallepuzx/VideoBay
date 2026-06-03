<?php

declare(strict_types=1);

namespace App\Streaming\Application\ListPendingVideoFiles;

use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;

class ListPendingVideoFiles
{
    public function __construct(
        private VideoFileStorageInterface $storage,
    ) {}

    public function __invoke(): ListPendingVideoFilesResponse
    {
        return ListPendingVideoFilesResponse::create(
            $this->storage->listFilesInRoot(),
            $this->storage->listFilesInOriginals(),
        );
    }
}
