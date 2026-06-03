<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Wishlist\Application\ListUserWishlist\ListUserWishlist;
use App\Wishlist\Infrastructure\Entrypoint\Http\Requests\ListMyWishlistRequest;
use Illuminate\Http\JsonResponse;

class ListMyWishlistController
{
    public function __construct(
        private ListUserWishlist $listUserWishlist,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ListMyWishlistRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->listUserWishlist)(
            $context->userId()->value(),
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
