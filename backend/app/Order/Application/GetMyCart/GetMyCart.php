<?php

declare(strict_types=1);

namespace App\Order\Application\GetMyCart;

use App\Order\Domain\Entity\CartItem;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Shared\Domain\ValueObject\Uuid;

class GetMyCart
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private CopyDetailsProviderInterface $copyDetailsProvider,
    ) {}

    public function __invoke(string $userUuid): GetMyCartResponse
    {
        $cart = $this->cartRepository->findByUserId(Uuid::create($userUuid));

        if ($cart === null || $cart->isEmpty()) {
            return GetMyCartResponse::empty();
        }

        $copyIds = array_map(fn (CartItem $item): Uuid => $item->physicalCopyId(), $cart->items());
        $details = $this->copyDetailsProvider->getByIds($copyIds);

        return GetMyCartResponse::fromCart($cart, $details);
    }
}
