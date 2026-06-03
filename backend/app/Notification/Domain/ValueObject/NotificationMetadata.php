<?php

declare(strict_types=1);

namespace App\Notification\Domain\ValueObject;

class NotificationMetadata
{
    /** @param array<string, mixed> $data */
    private function __construct(private array $data) {}

    /** @param array<string, mixed> $data */
    public static function create(array $data): self
    {
        self::assertSerializable($data);

        return new self($data);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    public function equals(self $other): bool
    {
        return $this->data === $other->data;
    }

    private static function assertSerializable(mixed $value): void
    {
        if ($value === null || is_scalar($value)) {
            return;
        }

        if (! is_array($value)) {
            throw new \InvalidArgumentException('Notification metadata must be JSON serializable.');
        }

        foreach ($value as $item) {
            self::assertSerializable($item);
        }
    }
}
