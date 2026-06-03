<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

class SubtitleSource
{
    private const EXTERNAL = 'external';

    private const USER_UPLOAD = 'user_upload';

    private const ADMIN_UPLOAD = 'admin_upload';

    private const VALID_SOURCES = [
        self::EXTERNAL,
        self::USER_UPLOAD,
        self::ADMIN_UPLOAD,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_SOURCES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid subtitle source "%s".', $value));
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function external(): self
    {
        return new self(self::EXTERNAL);
    }

    public static function userUpload(): self
    {
        return new self(self::USER_UPLOAD);
    }

    public static function adminUpload(): self
    {
        return new self(self::ADMIN_UPLOAD);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isExternal(): bool
    {
        return $this->value === self::EXTERNAL;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
