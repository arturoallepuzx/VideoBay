<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderStatusCounts;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;

class GetOrderStatusCounts
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(): GetOrderStatusCountsResponse
    {
        return GetOrderStatusCountsResponse::create($this->orderRepository->countByStatus());
    }
}
