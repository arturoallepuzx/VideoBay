<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

class AccessibilitySettings
{
    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    private function __construct(array $data)
    {
        $this->data = $data;
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): self
    {
        return new self($data);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    public function equals(self $other): bool
    {
        return $this->data === $other->data;
    }
}
