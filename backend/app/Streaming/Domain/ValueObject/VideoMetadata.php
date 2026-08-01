<?php

declare(strict_types=1);

namespace App\Streaming\Domain\ValueObject;

final readonly class VideoMetadata
{
    private function __construct(
        private int $durationSeconds,
        private int $fileSizeBytes,
        private ?string $audioLanguage,
    ) {}

    public static function create(int $durationSeconds, int $fileSizeBytes, ?string $audioLanguage): self
    {
        if ($durationSeconds < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid video duration %d.', $durationSeconds));
        }

        if ($fileSizeBytes < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid video size %d.', $fileSizeBytes));
        }

        $normalizedLanguage = $audioLanguage !== null ? trim($audioLanguage) : null;

        return new self(
            $durationSeconds,
            $fileSizeBytes,
            $normalizedLanguage !== '' ? $normalizedLanguage : null,
        );
    }

    public function durationSeconds(): int
    {
        return $this->durationSeconds;
    }

    public function fileSizeBytes(): int
    {
        return $this->fileSizeBytes;
    }

    public function audioLanguage(): ?string
    {
        return $this->audioLanguage;
    }
}
