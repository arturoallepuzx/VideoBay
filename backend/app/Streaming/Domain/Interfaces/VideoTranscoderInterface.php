<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

interface VideoTranscoderInterface
{
    /** @return array{duration_seconds: int, file_size_bytes: int, audio_language: ?string} */
    public function probe(string $absolutePath): array;

    public function transcodeToMp4(string $inputAbsolutePath, string $outputAbsolutePath): void;
}
