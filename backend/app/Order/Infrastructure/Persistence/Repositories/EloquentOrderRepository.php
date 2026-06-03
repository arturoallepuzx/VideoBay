<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderItem;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Order\Domain\ValueObject\PickupCode;
use App\Order\Domain\ValueObject\Quantity;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\PhysicalCopyIdResolverInterface;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private EloquentOrder $model,
        private UserIdResolverInterface $userIdResolver,
        private PhysicalCopyIdResolverInterface $physicalCopyIdResolver,
    ) {}

    public function create(Order $order): void
    {
        $model = $this->model->newQuery()->create([
            'uuid' => $order->id()->value(),
            'user_id' => $this->userIdResolver->toInternalId($order->userId()),
            'status' => $order->status()->value(),
            'total_cents' => $order->totalCents(),
            'stripe_session_id' => $order->stripeSessionId(),
            'stripe_payment_intent_id' => $order->stripePaymentIntentId(),
            'pickup_code' => $order->pickupCode()?->value(),
            'paid_at' => $order->paidAt()?->value(),
            'ready_at' => $order->readyAt()?->value(),
            'picked_up_at' => $order->pickedUpAt()?->value(),
            'cancelled_at' => $order->cancelledAt()?->value(),
            'expires_at' => $order->expiresAt()?->value(),
            'created_at' => $order->createdAt()->value(),
            'updated_at' => $order->updatedAt()->value(),
        ]);

        foreach ($order->items() as $item) {
            $model->items()->create([
                'physical_copy_id' => $this->physicalCopyIdResolver->toInternalId($item->physicalCopyId()),
                'quantity' => $item->quantity()->value(),
                'unit_price_cents' => $item->unitPriceCents(),
                'movie_title_snapshot' => $item->movieTitleSnapshot(),
                'format_snapshot' => $item->formatSnapshot(),
                'condition_snapshot' => $item->conditionSnapshot(),
            ]);
        }
    }

    public function update(Order $order): void
    {
        $this->model->newQuery()
            ->where('uuid', $order->id()->value())
            ->update([
                'status' => $order->status()->value(),
                'stripe_session_id' => $order->stripeSessionId(),
                'stripe_payment_intent_id' => $order->stripePaymentIntentId(),
                'pickup_code' => $order->pickupCode()?->value(),
                'paid_at' => $order->paidAt()?->value(),
                'ready_at' => $order->readyAt()?->value(),
                'picked_up_at' => $order->pickedUpAt()?->value(),
                'cancelled_at' => $order->cancelledAt()?->value(),
                'updated_at' => $order->updatedAt()->value(),
            ]);
    }

    public function findByUuid(Uuid $uuid): ?Order
    {
        $model = $this->model->newQuery()
            ->with('items')
            ->where('uuid', $uuid->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByUuidForUpdate(Uuid $uuid): ?Order
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        $model->load('items');

        return $this->toDomainEntity($model);
    }

    public function findByStripeSessionIdForUpdate(string $stripeSessionId): ?Order
    {
        $model = $this->model->newQuery()
            ->where('stripe_session_id', $stripeSessionId)
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        $model->load('items');

        return $this->toDomainEntity($model);
    }

    public function existsByPickupCode(PickupCode $pickupCode): bool
    {
        return $this->model->newQuery()
            ->where('pickup_code', $pickupCode->value())
            ->exists();
    }

    public function findByPickupCode(PickupCode $pickupCode): ?Order
    {
        $model = $this->model->newQuery()
            ->with('items')
            ->where('pickup_code', $pickupCode->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByPickupCodeForUpdate(PickupCode $pickupCode): ?Order
    {
        $model = $this->model->newQuery()
            ->where('pickup_code', $pickupCode->value())
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        $model->load('items');

        return $this->toDomainEntity($model);
    }

    public function listByUser(Uuid $userId, int $page, int $perPage): array
    {
        $base = $this->model->newQuery()
            ->where('user_id', $this->userIdResolver->toInternalId($userId));

        $total = (clone $base)->count();

        $models = $base
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->forPage($page, $perPage)
            ->get();

        return [
            'orders' => $models->map(fn (EloquentOrder $m): Order => $this->toDomainEntity($m))->all(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function listAll(?OrderStatus $status, int $page, int $perPage): array
    {
        $base = $this->model->newQuery();

        if ($status !== null) {
            $base->where('status', $status->value());
        }

        $total = (clone $base)->count();

        $models = $base
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->forPage($page, $perPage)
            ->get();

        return [
            'orders' => $models->map(fn (EloquentOrder $m): Order => $this->toDomainEntity($m))->all(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function countByStatus(): array
    {
        return $this->model->newQuery()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    public function findExpired(DomainDateTime $now, int $limit): array
    {
        return $this->model->newQuery()
            ->with('items')
            ->where('status', OrderStatus::pendingPayment()->value())
            ->where('expires_at', '<', $now->value())
            ->limit($limit)
            ->get()
            ->map(fn (EloquentOrder $m): Order => $this->toDomainEntity($m))
            ->all();
    }

    private function toDomainEntity(EloquentOrder $model): Order
    {
        $items = [];

        foreach ($model->items as $itemModel) {
            $copyUuid = $this->physicalCopyIdResolver->toDomainUuid((int) $itemModel->physical_copy_id);
            $items[] = OrderItem::create(
                $copyUuid,
                Quantity::create((int) $itemModel->quantity),
                (int) $itemModel->unit_price_cents,
                (string) $itemModel->movie_title_snapshot,
                (string) $itemModel->format_snapshot,
                (string) $itemModel->condition_snapshot,
            );
        }

        $userUuid = $this->userIdResolver->toDomainUuid((int) $model->user_id);

        return Order::fromPersistence(
            (string) $model->uuid,
            $userUuid->value(),
            (string) $model->status,
            (int) $model->total_cents,
            $model->stripe_session_id !== null ? (string) $model->stripe_session_id : null,
            $model->stripe_payment_intent_id !== null ? (string) $model->stripe_payment_intent_id : null,
            $model->pickup_code !== null ? (string) $model->pickup_code : null,
            $model->paid_at?->toDateTimeImmutable(),
            $model->ready_at?->toDateTimeImmutable(),
            $model->picked_up_at?->toDateTimeImmutable(),
            $model->cancelled_at?->toDateTimeImmutable(),
            $model->expires_at?->toDateTimeImmutable(),
            $items,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
