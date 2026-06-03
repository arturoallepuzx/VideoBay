<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrderForPickup\GetOrderForPickup;
use Illuminate\Http\JsonResponse;

class GetOrderForPickupController
{
    public function __construct(
        private GetOrderForPickup $getOrderForPickup,
    ) {}

    public function __invoke(string $pickupCode): JsonResponse
    {
        $response = ($this->getOrderForPickup)($pickupCode);

        return new JsonResponse($response->toArray());
    }
}
