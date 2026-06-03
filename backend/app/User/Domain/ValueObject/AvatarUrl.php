<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

class AvatarUrl
{
    private const MAX_LENGTH = 255;

    private const ALLOWED_SCHEMES = ['http', 'https'];

    private string $value;

    private function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Avatar URL cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Avatar URL cannot exceed %d characters.', self::MAX_LENGTH)
            );
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException(sprintf('Invalid avatar URL "%s".', $trimmed));
        }

        $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
        if (! in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Avatar URL scheme must be one of: %s.', implode(', ', self::ALLOWED_SCHEMES))
            );
        }

        $this->value = $trimmed;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
