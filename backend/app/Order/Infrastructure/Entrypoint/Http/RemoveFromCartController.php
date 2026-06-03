<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\RemoveFromCart\RemoveFromCart;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class RemoveFromCartController
{
    public function __construct(
        private RemoveFromCart $removeFromCart,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(string $copyId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->removeFromCart)($context->userId()->value(), $copyId);

        return new JsonResponse($response->toArray());
    }
}
