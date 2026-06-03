<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\ListAllOrders\ListAllOrders;
use App\Order\Infrastructure\Entrypoint\Http\Requests\ListAllOrdersRequest;
use Illuminate\Http\JsonResponse;

class ListAllOrdersController
{
    public function __construct(
        private ListAllOrders $listAllOrders,
    ) {}

    public function __invoke(ListAllOrdersRequest $request): JsonResponse
    {
        $response = ($this->listAllOrders)(
            $request->validated('status'),
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
