<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderForPickup;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\PickupCode;

class GetOrderForPickup
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(string $pickupCode): GetOrderForPickupResponse
    {
        $code = PickupCode::create($pickupCode);

        $order = $this->orderRepository->findByPickupCode($code);

        if ($order === null) {
            throw OrderNotFoundException::forPickupCode($code);
        }

        return GetOrderForPickupResponse::create($order);
    }
}
