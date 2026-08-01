<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Streaming\Domain\ValueObject\VideoLocation;

interface VideoStorageInterface
{
    public function locationForVideo(string $name): VideoLocation;

    public function exists(VideoLocation $location): bool;

    public function delete(VideoLocation $location): void;

    public function rename(VideoLocation $from, VideoLocation $to): void;
}
