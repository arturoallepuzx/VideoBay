<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

class CopyCondition
{
    private const NEW = 'new';

    private const LIKE_NEW = 'like_new';

    private const GOOD = 'good';

    private const FAIR = 'fair';

    private const VALID_CONDITIONS = [
        self::NEW,
        self::LIKE_NEW,
        self::GOOD,
        self::FAIR,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_CONDITIONS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid copy condition "%s". Allowed: %s.',
                    $value,
                    implode(', ', self::VALID_CONDITIONS),
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
        return self::VALID_CONDITIONS;
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
