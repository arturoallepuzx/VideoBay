<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\UpdatePricingRules\UpdatePricingRules;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\UpdatePricingRulesRequest;
use Illuminate\Http\JsonResponse;

class UpdatePricingRulesController
{
    public function __construct(
        private UpdatePricingRules $updatePricingRules,
    ) {}

    public function __invoke(UpdatePricingRulesRequest $request): JsonResponse
    {
        $response = ($this->updatePricingRules)(
            $request->validated('base_prices_cents'),
            $request->validated('condition_multipliers'),
            $request->validated('buy_margin_percent'),
        );

        return new JsonResponse($response->toArray());
    }
}
