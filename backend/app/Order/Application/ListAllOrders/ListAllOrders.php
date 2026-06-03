<?php

declare(strict_types=1);

namespace App\Order\Application\ListAllOrders;

use App\Order\Application\ListMyOrders\ListMyOrdersResponse;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderStatus;

class ListAllOrders
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(?string $status, int $page, int $perPage): ListMyOrdersResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $statusVo = $status !== null ? OrderStatus::create($status) : null;

        $result = $this->orderRepository->listAll($statusVo, $page, $perPage);

        return ListMyOrdersResponse::create($result);
    }
}
