<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\MarkOrderAsPickedUpByCode\MarkOrderAsPickedUpByCode;
use Illuminate\Http\JsonResponse;

class MarkOrderAsPickedUpByCodeController
{
    public function __construct(
        private MarkOrderAsPickedUpByCode $markOrderAsPickedUpByCode,
    ) {}

    public function __invoke(string $pickupCode): JsonResponse
    {
        $response = ($this->markOrderAsPickedUpByCode)($pickupCode);

        return new JsonResponse($response->toArray());
    }
}
