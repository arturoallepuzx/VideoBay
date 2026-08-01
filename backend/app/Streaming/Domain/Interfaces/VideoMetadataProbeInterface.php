<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;

interface VideoMetadataProbeInterface
{
    public function probe(VideoLocation $location): VideoMetadata;
}
