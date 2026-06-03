<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

class SaleProposalStatus
{
    private const PROPOSED = 'proposed';

    private const ACCEPTED = 'accepted';

    private const REJECTED = 'rejected';

    private const VALID_STATUSES = [
        self::PROPOSED,
        self::ACCEPTED,
        self::REJECTED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid sale proposal status "%s". Allowed: %s.',
                    $value,
                    implode(', ', self::VALID_STATUSES),
                )
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function proposed(): self
    {
        return new self(self::PROPOSED);
    }

    public static function accepted(): self
    {
        return new self(self::ACCEPTED);
    }

    public static function rejected(): self
    {
        return new self(self::REJECTED);
    }

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return self::VALID_STATUSES;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isProposed(): bool
    {
        return $this->value === self::PROPOSED;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
