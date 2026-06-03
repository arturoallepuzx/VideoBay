<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

class SubtitleFormat
{
    private const SRT = 'srt';

    private const VTT = 'vtt';

    private const VALID_FORMATS = [
        self::SRT,
        self::VTT,
    ];

    private string $value;

    private function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (! in_array($normalized, self::VALID_FORMATS, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid subtitle format "%s". Allowed: srt, vtt.', $value));
        }

        $this->value = $normalized;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function srt(): self
    {
        return new self(self::SRT);
    }

    public static function vtt(): self
    {
        return new self(self::VTT);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isSrt(): bool
    {
        return $this->value === self::SRT;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
