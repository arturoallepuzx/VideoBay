<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\GetPricingRules\GetPricingRules;
use Illuminate\Http\JsonResponse;

class GetPricingRulesController
{
    public function __construct(
        private GetPricingRules $getPricingRules,
    ) {}

    public function __invoke(): JsonResponse
    {
        $response = ($this->getPricingRules)();

        return new JsonResponse($response->toArray());
    }
}
