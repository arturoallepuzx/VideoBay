<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\Cart;
use App\Order\Domain\Entity\CartItem;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\ValueObject\Quantity;
use App\Order\Infrastructure\Persistence\Models\EloquentCart;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\PhysicalCopyIdResolverInterface;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;

class EloquentCartRepository implements CartRepositoryInterface
{
    public function __construct(
        private EloquentCart $model,
        private UserIdResolverInterface $userIdResolver,
        private PhysicalCopyIdResolverInterface $physicalCopyIdResolver,
    ) {}

    public function findByUserId(Uuid $userId): ?Cart
    {
        $model = $this->model->newQuery()
            ->with('items')
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByUserIdForUpdate(Uuid $userId): ?Cart
    {
        $model = $this->model->newQuery()
            ->with('items')
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->lockForUpdate()
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function save(Cart $cart): void
    {
        $model = $this->model->newQuery()->updateOrCreate(
            ['user_id' => $this->userIdResolver->toInternalId($cart->userId())],
            [
                'uuid' => $cart->id()->value(),
                'created_at' => $cart->createdAt()->value(),
                'updated_at' => $cart->updatedAt()->value(),
            ],
        );

        $model->items()->delete();

        foreach ($cart->items() as $item) {
            $model->items()->create([
                'physical_copy_id' => $this->physicalCopyIdResolver->toInternalId($item->physicalCopyId()),
                'quantity' => $item->quantity()->value(),
            ]);
        }
    }

    public function delete(Cart $cart): void
    {
        $this->model->newQuery()
            ->where('uuid', $cart->id()->value())
            ->delete();
    }

    private function toDomainEntity(EloquentCart $model): Cart
    {
        $items = [];

        foreach ($model->items as $itemModel) {
            $copyUuid = $this->physicalCopyIdResolver->toDomainUuid((int) $itemModel->physical_copy_id);
            $items[] = CartItem::create($copyUuid, Quantity::create((int) $itemModel->quantity));
        }

        $userUuid = $this->userIdResolver->toDomainUuid((int) $model->user_id);

        return Cart::fromPersistence(
            (string) $model->uuid,
            $userUuid->value(),
            $items,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
