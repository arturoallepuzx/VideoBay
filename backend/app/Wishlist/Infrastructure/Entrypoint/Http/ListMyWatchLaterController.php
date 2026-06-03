<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Wishlist\Application\ListUserWatchLater\ListUserWatchLater;
use App\Wishlist\Infrastructure\Entrypoint\Http\Requests\ListMyWatchLaterRequest;
use Illuminate\Http\JsonResponse;

class ListMyWatchLaterController
{
    public function __construct(
        private ListUserWatchLater $listUserWatchLater,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ListMyWatchLaterRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->listUserWatchLater)(
            $context->userId()->value(),
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
