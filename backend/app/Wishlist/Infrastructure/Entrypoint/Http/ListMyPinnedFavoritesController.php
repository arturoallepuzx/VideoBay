<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Wishlist\Application\ListMyPinnedFavorites\ListMyPinnedFavorites;
use Illuminate\Http\JsonResponse;

class ListMyPinnedFavoritesController
{
    public function __construct(
        private ListMyPinnedFavorites $listMyPinnedFavorites,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->listMyPinnedFavorites)($context->userId()->value());

        return new JsonResponse($response->toArray());
    }
}
