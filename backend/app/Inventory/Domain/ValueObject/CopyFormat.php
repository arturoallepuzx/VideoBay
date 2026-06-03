<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

class CopyFormat
{
    private const DVD = 'DVD';

    private const BLURAY = 'BLURAY';

    private const UHD_4K = 'UHD_4K';

    private const VHS = 'VHS';

    private const VALID_FORMATS = [
        self::DVD,
        self::BLURAY,
        self::UHD_4K,
        self::VHS,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_FORMATS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid copy format "%s". Allowed: %s.',
                    $value,
                    implode(', ', self::VALID_FORMATS),
                )
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return self::VALID_FORMATS;
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
