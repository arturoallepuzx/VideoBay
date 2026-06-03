<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

class UserRole
{
    private const CUSTOMER = 'customer';

    private const ADMIN = 'admin';

    private const VALID_ROLES = [
        self::CUSTOMER,
        self::ADMIN,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid user role "%s". Allowed: %s.',
                    $value,
                    implode(', ', self::VALID_ROLES),
                )
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function customer(): self
    {
        return new self(self::CUSTOMER);
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return self::VALID_ROLES;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isCustomer(): bool
    {
        return $this->value === self::CUSTOMER;
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }
}
