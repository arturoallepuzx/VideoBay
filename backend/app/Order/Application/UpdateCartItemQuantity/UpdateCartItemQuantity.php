<?php

declare(strict_types=1);

namespace App\Order\Application\UpdateCartItemQuantity;

use App\Order\Application\GetMyCart\GetMyCartResponse;
use App\Order\Domain\Entity\Cart;
use App\Order\Domain\Entity\CartItem;
use App\Order\Domain\Exception\CopyNotPurchasableException;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateCartItemQuantity
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private CopyDetailsProviderInterface $copyDetailsProvider,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $userUuid, string $copyUuid, int $quantity): GetMyCartResponse
    {
        $userId = Uuid::create($userUuid);
        $copyId = Uuid::create($copyUuid);
        $newQuantity = Quantity::create($quantity);

        $detail = $this->copyDetailsProvider->getByIds([$copyId])[$copyId->value()] ?? null;

        if ($detail === null || ! $detail->isAvailable()) {
            throw CopyNotPurchasableException::unavailable($copyId);
        }

        if (! $detail->canFulfill($quantity)) {
            throw CopyNotPurchasableException::insufficientStock($copyId, $quantity, $detail->stockAvailable());
        }

        $cart = $this->transactionRunner->run(function () use ($userId, $copyId, $newQuantity): ?Cart {
            $cart = $this->cartRepository->findByUserIdForUpdate($userId);

            if ($cart === null) {
                return null;
            }

            $cart->updateItemQuantity($copyId, $newQuantity);
            $this->cartRepository->save($cart);

            return $cart;
        });

        if ($cart === null) {
            return GetMyCartResponse::empty();
        }

        $copyIds = array_map(fn (CartItem $item): Uuid => $item->physicalCopyId(), $cart->items());

        return GetMyCartResponse::fromCart($cart, $this->copyDetailsProvider->getByIds($copyIds));
    }
}
