<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Wishlist\Application\AddToWishlist\AddToWishlist;
use Illuminate\Http\JsonResponse;

class AddToWishlistController
{
    public function __construct(
        private AddToWishlist $addToWishlist,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(string $movieId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->addToWishlist)($context->userId()->value(), $movieId);

        return new JsonResponse($response->toArray(), 201);
    }
}
