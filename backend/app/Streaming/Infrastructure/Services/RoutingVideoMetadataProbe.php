<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\Interfaces\VideoMetadataProbeInterface;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;

class RoutingVideoMetadataProbe implements VideoMetadataProbeInterface
{
    public function __construct(
        private VideoMetadataProbeInterface $localProbe,
        private VideoMetadataProbeInterface $r2Probe,
    ) {}

    public function probe(VideoLocation $location): VideoMetadata
    {
        return $this->adapterFor($location->disk())->probe($location);
    }

    private function adapterFor(StorageDisk $disk): VideoMetadataProbeInterface
    {
        return $disk->isR2() ? $this->r2Probe : $this->localProbe;
    }
}
