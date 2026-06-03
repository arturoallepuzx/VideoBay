<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Interfaces;

use App\Subtitle\Domain\ValueObject\SubtitleFormat;

interface SubtitleConverterInterface
{
    public function toWebVtt(string $contents, SubtitleFormat $format): string;
}
