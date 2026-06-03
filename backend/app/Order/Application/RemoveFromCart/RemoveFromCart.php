<?php

declare(strict_types=1);

namespace App\Order\Application\RemoveFromCart;

use App\Order\Application\GetMyCart\GetMyCartResponse;
use App\Order\Domain\Entity\Cart;
use App\Order\Domain\Entity\CartItem;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class RemoveFromCart
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private CopyDetailsProviderInterface $copyDetailsProvider,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $userUuid, string $copyUuid): GetMyCartResponse
    {
        $userId = Uuid::create($userUuid);
        $copyId = Uuid::create($copyUuid);

        $cart = $this->transactionRunner->run(function () use ($userId, $copyId): ?Cart {
            $cart = $this->cartRepository->findByUserIdForUpdate($userId);

            if ($cart === null) {
                return null;
            }

            $cart->removeItem($copyId);
            $this->cartRepository->save($cart);

            return $cart;
        });

        if ($cart === null || $cart->isEmpty()) {
            return GetMyCartResponse::empty();
        }

        $copyIds = array_map(fn (CartItem $item): Uuid => $item->physicalCopyId(), $cart->items());

        return GetMyCartResponse::fromCart($cart, $this->copyDetailsProvider->getByIds($copyIds));
    }
}
