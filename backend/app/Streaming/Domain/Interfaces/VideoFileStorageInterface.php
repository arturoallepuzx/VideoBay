<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Streaming\Domain\ValueObject\ByteRange;
use App\Streaming\Domain\ValueObject\StoredObject;
use App\Streaming\Domain\ValueObject\VideoLocation;

interface VideoFileStorageInterface extends VideoStorageInterface
{
    /** @return list<StoredObject> */
    public function listVideos(): array;

    /** @return iterable<string> */
    public function readChunks(VideoLocation $location, ?ByteRange $range): iterable;
}
