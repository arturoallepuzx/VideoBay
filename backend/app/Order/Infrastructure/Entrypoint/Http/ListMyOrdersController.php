<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\ListMyOrders\ListMyOrders;
use App\Order\Infrastructure\Entrypoint\Http\Requests\ListMyOrdersRequest;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class ListMyOrdersController
{
    public function __construct(
        private ListMyOrders $listMyOrders,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ListMyOrdersRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->listMyOrders)(
            $context->userId()->value(),
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
