<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrderStatusCounts\GetOrderStatusCounts;
use Illuminate\Http\JsonResponse;

class GetOrderStatusCountsController
{
    public function __construct(
        private GetOrderStatusCounts $getOrderStatusCounts,
    ) {}

    public function __invoke(): JsonResponse
    {
        $response = ($this->getOrderStatusCounts)();

        return new JsonResponse($response->toArray());
    }
}
