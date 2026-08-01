<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\ValueObject\ByteRange;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\StoredObject;
use App\Streaming\Domain\ValueObject\VideoLocation;

class RoutingVideoFileStorage implements VideoFileStorageInterface
{
    public function __construct(
        private VideoFileStorageInterface $localStorage,
        private VideoFileStorageInterface $r2Storage,
        private StorageDisk $ingestionDisk,
    ) {}

    public function locationForVideo(string $name): VideoLocation
    {
        return $this->adapterFor($this->ingestionDisk)->locationForVideo($name);
    }

    public function exists(VideoLocation $location): bool
    {
        return $this->adapterFor($location->disk())->exists($location);
    }

    public function delete(VideoLocation $location): void
    {
        $this->adapterFor($location->disk())->delete($location);
    }

    public function rename(VideoLocation $from, VideoLocation $to): void
    {
        if (! $from->disk()->equals($to->disk())) {
            throw new \InvalidArgumentException('Cannot rename a video file across storage disks.');
        }

        $this->adapterFor($from->disk())->rename($from, $to);
    }

    /** @return list<StoredObject> */
    public function listVideos(): array
    {
        return $this->adapterFor($this->ingestionDisk)->listVideos();
    }

    /** @return iterable<string> */
    public function readChunks(VideoLocation $location, ?ByteRange $range): iterable
    {
        return $this->adapterFor($location->disk())->readChunks($location, $range);
    }

    private function adapterFor(StorageDisk $disk): VideoFileStorageInterface
    {
        return $disk->isR2() ? $this->r2Storage : $this->localStorage;
    }
}
