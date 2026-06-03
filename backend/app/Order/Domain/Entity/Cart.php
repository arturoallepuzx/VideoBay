<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Cart
{
    private bool $modified = false;

    /**
     * @param  list<CartItem>  $items
     */
    private function __construct(
        private Uuid $id,
        private Uuid $userId,
        private array $items,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(Uuid $userId): self
    {
        $now = DomainDateTime::now();

        return new self(Uuid::generate(), $userId, [], $now, $now);
    }

    /**
     * @param  list<CartItem>  $items
     */
    public static function fromPersistence(
        string $id,
        string $userId,
        array $items,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($userId),
            $items,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function addItem(Uuid $physicalCopyId, Quantity $quantity): void
    {
        $existing = $this->findItem($physicalCopyId);

        if ($existing !== null) {
            $existing->increaseQuantity($quantity->value());
        } else {
            $this->items[] = CartItem::create($physicalCopyId, $quantity);
        }

        $this->touch();
    }

    public function updateItemQuantity(Uuid $physicalCopyId, Quantity $quantity): void
    {
        $existing = $this->findItem($physicalCopyId);

        if ($existing === null) {
            return;
        }

        $existing->changeQuantity($quantity);
        $this->touch();
    }

    public function removeItem(Uuid $physicalCopyId): void
    {
        $filtered = array_values(array_filter(
            $this->items,
            fn (CartItem $item): bool => ! $item->physicalCopyId()->equals($physicalCopyId),
        ));

        if (count($filtered) === count($this->items)) {
            return;
        }

        $this->items = $filtered;
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    /**
     * @return list<CartItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
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

    private function findItem(Uuid $physicalCopyId): ?CartItem
    {
        foreach ($this->items as $item) {
            if ($item->physicalCopyId()->equals($physicalCopyId)) {
                return $item;
            }
        }

        return null;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
