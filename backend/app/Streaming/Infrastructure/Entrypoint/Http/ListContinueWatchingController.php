<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Streaming\Application\ListContinueWatching\ListContinueWatching;
use App\Streaming\Infrastructure\Entrypoint\Http\Requests\ListContinueWatchingRequest;
use Illuminate\Http\JsonResponse;

class ListContinueWatchingController
{
    public function __construct(
        private ListContinueWatching $listContinueWatching,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ListContinueWatchingRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->listContinueWatching)(
            $context->userId()->value(),
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
