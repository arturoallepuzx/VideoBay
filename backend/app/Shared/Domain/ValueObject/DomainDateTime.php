<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

class DomainDateTime
{
    private \DateTimeImmutable $value;

    private function __construct(?\DateTimeImmutable $value = null)
    {
        $this->value = $value ?? new \DateTimeImmutable;
    }

    public static function create(?\DateTimeImmutable $value = null): self
    {
        return new self($value);
    }

    public static function now(): self
    {
        return self::create(new \DateTimeImmutable);
    }

    public function value(): \DateTimeImmutable
    {
        return $this->value;
    }

    public function format(string $format): string
    {
        return $this->value->format($format);
    }

    public function equals(self $other): bool
    {
        return $this->value == $other->value;
    }
}
