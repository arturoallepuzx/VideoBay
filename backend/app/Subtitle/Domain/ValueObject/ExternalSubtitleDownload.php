<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

class ExternalSubtitleDownload
{
    private function __construct(
        private string $contents,
        private SubtitleFormat $format,
    ) {}

    public static function create(string $contents, SubtitleFormat $format): self
    {
        if (trim($contents) === '') {
            throw new \InvalidArgumentException('External subtitle contents cannot be empty.');
        }

        return new self($contents, $format);
    }

    public function contents(): string
    {
        return $this->contents;
    }

    public function format(): SubtitleFormat
    {
        return $this->format;
    }
}
