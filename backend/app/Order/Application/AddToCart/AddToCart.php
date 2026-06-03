<?php

declare(strict_types=1);

namespace App\Order\Application\AddToCart;

use App\Order\Application\GetMyCart\GetMyCartResponse;
use App\Order\Domain\Entity\Cart;
use App\Order\Domain\Entity\CartItem;
use App\Order\Domain\Exception\CopyNotPurchasableException;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class AddToCart
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
        $addedQuantity = Quantity::create($quantity);

        $cart = $this->transactionRunner->run(function () use ($userId, $copyId, $quantity, $addedQuantity): Cart {
            $cart = $this->cartRepository->findByUserIdForUpdate($userId) ?? Cart::dddCreate($userId);

            $requestedTotal = $this->currentQuantity($cart, $copyId) + $quantity;
            $this->assertPurchasable($copyId, $requestedTotal);

            $cart->addItem($copyId, $addedQuantity);
            $this->cartRepository->save($cart);

            return $cart;
        });

        return $this->cartView($cart);
    }

    private function currentQuantity(Cart $cart, Uuid $copyId): int
    {
        foreach ($cart->items() as $item) {
            if ($item->physicalCopyId()->equals($copyId)) {
                return $item->quantity()->value();
            }
        }

        return 0;
    }

    private function assertPurchasable(Uuid $copyId, int $requestedTotal): void
    {
        $detail = $this->copyDetailsProvider->getByIds([$copyId])[$copyId->value()] ?? null;

        if ($detail === null || ! $detail->isAvailable()) {
            throw CopyNotPurchasableException::unavailable($copyId);
        }

        if (! $detail->canFulfill($requestedTotal)) {
            throw CopyNotPurchasableException::insufficientStock($copyId, $requestedTotal, $detail->stockAvailable());
        }
    }

    private function cartView(Cart $cart): GetMyCartResponse
    {
        $copyIds = array_map(fn (CartItem $item): Uuid => $item->physicalCopyId(), $cart->items());

        return GetMyCartResponse::fromCart($cart, $this->copyDetailsProvider->getByIds($copyIds));
    }
}
