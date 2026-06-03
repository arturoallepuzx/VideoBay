<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

class ImagePath
{
    private const MAX_LENGTH = 255;

    private string $value;

    private function __construct(string $value)
    {
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Image path cannot exceed %d characters.', self::MAX_LENGTH)
            );
        }

        if ($value === '' || $value[0] !== '/') {
            throw new \InvalidArgumentException('Image path must start with "/".');
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
