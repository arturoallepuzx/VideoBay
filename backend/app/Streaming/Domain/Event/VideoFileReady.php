<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Event;

use App\Shared\Domain\ValueObject\Uuid;

class VideoFileReady
{
    private function __construct(
        private Uuid $videoFileId,
        private Uuid $movieId,
    ) {}

    public static function create(Uuid $videoFileId, Uuid $movieId): self
    {
        return new self($videoFileId, $movieId);
    }

    public function videoFileId(): Uuid
    {
        return $this->videoFileId;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }
}
