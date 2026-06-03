<?php

declare(strict_types=1);

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Order\Domain\ValueObject\PickupCode;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

interface OrderRepositoryInterface
{
    public function create(Order $order): void;

    public function update(Order $order): void;

    public function findByUuid(Uuid $uuid): ?Order;

    public function findByUuidForUpdate(Uuid $uuid): ?Order;

    public function findByStripeSessionIdForUpdate(string $stripeSessionId): ?Order;

    public function existsByPickupCode(PickupCode $pickupCode): bool;

    public function findByPickupCode(PickupCode $pickupCode): ?Order;

    public function findByPickupCodeForUpdate(PickupCode $pickupCode): ?Order;

    /**
     * @return array{orders: list<Order>, total: int, page: int, totalPages: int}
     */
    public function listByUser(Uuid $userId, int $page, int $perPage): array;

    /**
     * @return array{orders: list<Order>, total: int, page: int, totalPages: int}
     */
    public function listAll(?OrderStatus $status, int $page, int $perPage): array;

    /**
     * @return array<string, int>
     */
    public function countByStatus(): array;

    /**
     * @return list<Order>
     */
    public function findExpired(DomainDateTime $now, int $limit): array;
}
