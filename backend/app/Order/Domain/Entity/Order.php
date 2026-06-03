<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\Exception\InvalidOrderTransitionException;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Order\Domain\ValueObject\PickupCode;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Order
{
    private bool $modified = false;

    /**
     * @param  list<OrderItem>  $items
     */
    private function __construct(
        private Uuid $id,
        private Uuid $userId,
        private OrderStatus $status,
        private int $totalCents,
        private ?string $stripeSessionId,
        private ?string $stripePaymentIntentId,
        private ?PickupCode $pickupCode,
        private ?DomainDateTime $paidAt,
        private ?DomainDateTime $readyAt,
        private ?DomainDateTime $pickedUpAt,
        private ?DomainDateTime $cancelledAt,
        private ?DomainDateTime $expiresAt,
        private array $items,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    /**
     * @param  list<OrderItem>  $items
     */
    public static function dddCreate(Uuid $userId, array $items, DomainDateTime $expiresAt): self
    {
        if ($items === []) {
            throw new \InvalidArgumentException('An order requires at least one item.');
        }

        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $userId,
            OrderStatus::pendingPayment(),
            self::sumItems($items),
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $expiresAt,
            $items,
            $now,
            $now,
        );
    }

    /**
     * @param  list<OrderItem>  $items
     */
    public static function fromPersistence(
        string $id,
        string $userId,
        string $status,
        int $totalCents,
        ?string $stripeSessionId,
        ?string $stripePaymentIntentId,
        ?string $pickupCode,
        ?\DateTimeImmutable $paidAt,
        ?\DateTimeImmutable $readyAt,
        ?\DateTimeImmutable $pickedUpAt,
        ?\DateTimeImmutable $cancelledAt,
        ?\DateTimeImmutable $expiresAt,
        array $items,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($userId),
            OrderStatus::create($status),
            $totalCents,
            $stripeSessionId,
            $stripePaymentIntentId,
            $pickupCode !== null ? PickupCode::create($pickupCode) : null,
            $paidAt !== null ? DomainDateTime::create($paidAt) : null,
            $readyAt !== null ? DomainDateTime::create($readyAt) : null,
            $pickedUpAt !== null ? DomainDateTime::create($pickedUpAt) : null,
            $cancelledAt !== null ? DomainDateTime::create($cancelledAt) : null,
            $expiresAt !== null ? DomainDateTime::create($expiresAt) : null,
            $items,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function attachStripeSession(string $stripeSessionId): void
    {
        $this->stripeSessionId = $stripeSessionId;
        $this->touch();
    }

    public function markPaid(string $stripePaymentIntentId): void
    {
        if (! $this->status->isPendingPayment()) {
            throw InvalidOrderTransitionException::cannotPay($this->id, $this->status);
        }

        $this->status = OrderStatus::paid();
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        $this->paidAt = DomainDateTime::now();
        $this->touch();
    }

    public function markReadyForPickup(PickupCode $pickupCode): void
    {
        if (! $this->status->isPaid()) {
            throw InvalidOrderTransitionException::cannotReadyForPickup($this->id, $this->status);
        }

        $this->status = OrderStatus::readyForPickup();
        $this->pickupCode = $pickupCode;
        $this->readyAt = DomainDateTime::now();
        $this->touch();
    }

    public function markPickedUp(): void
    {
        if (! $this->status->isReadyForPickup()) {
            throw InvalidOrderTransitionException::cannotPickUp($this->id, $this->status);
        }

        $this->status = OrderStatus::pickedUp();
        $this->pickedUpAt = DomainDateTime::now();
        $this->touch();
    }

    public function cancel(): void
    {
        if (! $this->status->isPendingPayment()) {
            throw InvalidOrderTransitionException::cannotCancel($this->id, $this->status);
        }

        $this->status = OrderStatus::cancelled();
        $this->cancelledAt = DomainDateTime::now();
        $this->touch();
    }

    public function isExpired(DomainDateTime $now): bool
    {
        return $this->status->isPendingPayment()
            && $this->expiresAt !== null
            && $this->expiresAt->value() < $now->value();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function totalCents(): int
    {
        return $this->totalCents;
    }

    public function stripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function stripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function pickupCode(): ?PickupCode
    {
        return $this->pickupCode;
    }

    public function paidAt(): ?DomainDateTime
    {
        return $this->paidAt;
    }

    public function readyAt(): ?DomainDateTime
    {
        return $this->readyAt;
    }

    public function pickedUpAt(): ?DomainDateTime
    {
        return $this->pickedUpAt;
    }

    public function cancelledAt(): ?DomainDateTime
    {
        return $this->cancelledAt;
    }

    public function expiresAt(): ?DomainDateTime
    {
        return $this->expiresAt;
    }

    /**
     * @return list<OrderItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    /**
     * @param  list<OrderItem>  $items
     */
    private static function sumItems(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            $total += $item->subtotalCents();
        }

        return $total;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
