<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Streaming\Application\ListMyWatchHistory\ListMyWatchHistory;
use App\Streaming\Infrastructure\Entrypoint\Http\Requests\ListMyWatchHistoryRequest;
use Illuminate\Http\JsonResponse;

class ListMyWatchHistoryController
{
    public function __construct(
        private ListMyWatchHistory $listMyWatchHistory,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ListMyWatchHistoryRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->listMyWatchHistory)(
            $context->userId()->value(),
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
