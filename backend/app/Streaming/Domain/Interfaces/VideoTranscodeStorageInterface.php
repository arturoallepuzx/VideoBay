<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Streaming\Domain\ValueObject\StoredObject;
use App\Streaming\Domain\ValueObject\VideoLocation;

interface VideoTranscodeStorageInterface extends VideoStorageInterface
{
    public function locationForOriginal(string $name): VideoLocation;

    /** @return list<StoredObject> */
    public function listOriginals(): array;

    public function store(string $temporaryPath, VideoLocation $destination): void;
}
