<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\Interfaces\VideoMetadataProbeInterface;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;

class LocalVideoMetadataProbe implements VideoMetadataProbeInterface
{
    public function __construct(private FfprobeMetadataReader $reader) {}

    public function probe(VideoLocation $location): VideoMetadata
    {
        return $this->reader->read($location, $location->key());
    }
}
