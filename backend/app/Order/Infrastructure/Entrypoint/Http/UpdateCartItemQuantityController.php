<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\UpdateCartItemQuantity\UpdateCartItemQuantity;
use App\Order\Infrastructure\Entrypoint\Http\Requests\UpdateCartItemQuantityRequest;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class UpdateCartItemQuantityController
{
    public function __construct(
        private UpdateCartItemQuantity $updateCartItemQuantity,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(UpdateCartItemQuantityRequest $request, string $copyId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->updateCartItemQuantity)(
            $context->userId()->value(),
            $copyId,
            $request->validated('quantity'),
        );

        return new JsonResponse($response->toArray());
    }
}
