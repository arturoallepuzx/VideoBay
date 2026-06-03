<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\AddToCart\AddToCart;
use App\Order\Infrastructure\Entrypoint\Http\Requests\AddToCartRequest;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class AddToCartController
{
    public function __construct(
        private AddToCart $addToCart,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(AddToCartRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->addToCart)(
            $context->userId()->value(),
            $request->validated('physical_copy_id'),
            $request->validated('quantity'),
        );

        return new JsonResponse($response->toArray());
    }
}
