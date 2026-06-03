<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

class OrderStatus
{
    private const PENDING_PAYMENT = 'pending_payment';

    private const PAID = 'paid';

    private const READY_FOR_PICKUP = 'ready_for_pickup';

    private const PICKED_UP = 'picked_up';

    private const CANCELLED = 'cancelled';

    private const REFUNDED = 'refunded';

    private const VALID_STATUSES = [
        self::PENDING_PAYMENT,
        self::PAID,
        self::READY_FOR_PICKUP,
        self::PICKED_UP,
        self::CANCELLED,
        self::REFUNDED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid order status "%s". Allowed: %s.', $value, implode(', ', self::VALID_STATUSES))
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function pendingPayment(): self
    {
        return new self(self::PENDING_PAYMENT);
    }

    public static function paid(): self
    {
        return new self(self::PAID);
    }

    public static function readyForPickup(): self
    {
        return new self(self::READY_FOR_PICKUP);
    }

    public static function pickedUp(): self
    {
        return new self(self::PICKED_UP);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function refunded(): self
    {
        return new self(self::REFUNDED);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::VALID_STATUSES;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPendingPayment(): bool
    {
        return $this->value === self::PENDING_PAYMENT;
    }

    public function isPaid(): bool
    {
        return $this->value === self::PAID;
    }

    public function isReadyForPickup(): bool
    {
        return $this->value === self::READY_FOR_PICKUP;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
