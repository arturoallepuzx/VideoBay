<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\MarkOrderAsPickedUp\MarkOrderAsPickedUp;
use Illuminate\Http\JsonResponse;

class MarkOrderAsPickedUpController
{
    public function __construct(
        private MarkOrderAsPickedUp $markOrderAsPickedUp,
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        $response = ($this->markOrderAsPickedUp)($orderId);

        return new JsonResponse($response->toArray());
    }
}
