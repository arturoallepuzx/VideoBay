<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetMyCart\GetMyCart;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class GetCartController
{
    public function __construct(
        private GetMyCart $getMyCart,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->getMyCart)($context->userId()->value());

        return new JsonResponse($response->toArray());
    }
}
