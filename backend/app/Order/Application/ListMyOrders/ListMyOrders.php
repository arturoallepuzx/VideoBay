<?php

declare(strict_types=1);

namespace App\Order\Application\ListMyOrders;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ListMyOrders
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(string $userUuid, int $page, int $perPage): ListMyOrdersResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $result = $this->orderRepository->listByUser(Uuid::create($userUuid), $page, $perPage);

        return ListMyOrdersResponse::create($result);
    }
}
