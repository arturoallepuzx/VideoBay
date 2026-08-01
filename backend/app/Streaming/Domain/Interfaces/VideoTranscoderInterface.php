<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

interface VideoTranscoderInterface
{
    public function transcodeToMp4(string $inputAbsolutePath, string $outputAbsolutePath): void;
}
