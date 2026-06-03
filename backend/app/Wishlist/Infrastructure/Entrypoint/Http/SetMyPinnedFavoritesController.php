<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Wishlist\Application\SetMyPinnedFavorites\SetMyPinnedFavorites;
use App\Wishlist\Infrastructure\Entrypoint\Http\Requests\SetMyPinnedFavoritesRequest;
use Illuminate\Http\JsonResponse;

class SetMyPinnedFavoritesController
{
    public function __construct(
        private SetMyPinnedFavorites $setMyPinnedFavorites,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(SetMyPinnedFavoritesRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        /** @var list<array{position: int, movie_uuid: string}> $slots */
        $slots = $request->validated('slots');

        $response = ($this->setMyPinnedFavorites)($context->userId()->value(), $slots);

        return new JsonResponse($response->toArray());
    }
}
