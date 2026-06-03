<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ServeSubtitleFile;

final readonly class ServeSubtitleFileResponse
{
    private function __construct(
        public string $absolutePath,
        public string $mimeType,
    ) {}

    public static function create(string $absolutePath): self
    {
        return new self($absolutePath, 'text/vtt; charset=UTF-8');
    }
}
