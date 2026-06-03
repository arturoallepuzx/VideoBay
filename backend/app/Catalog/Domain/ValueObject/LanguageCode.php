<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

class LanguageCode
{
    private const PATTERN = '/^[a-z]{2}(-[A-Z]{2})?$/';

    private string $value;

    private function __construct(string $value)
    {
        if (! preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid language code "%s". Expected format: "es" or "es-ES".', $value)
            );
        }

        $this->value = $value;
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
