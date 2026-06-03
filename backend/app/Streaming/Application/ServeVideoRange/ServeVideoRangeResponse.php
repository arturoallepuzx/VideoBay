<?php

declare(strict_types=1);

namespace App\Streaming\Application\ServeVideoRange;

use App\Streaming\Domain\Entity\VideoFile;

final readonly class ServeVideoRangeResponse
{
    private function __construct(
        public string $absolutePath,
        public string $mimeType,
    ) {}

    public static function create(VideoFile $videoFile): self
    {
        return new self(
            absolutePath: (string) $videoFile->processedPath(),
            mimeType: 'video/mp4',
        );
    }
}
