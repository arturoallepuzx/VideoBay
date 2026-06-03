<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Streaming\Application\GetPlaybackProgress\GetPlaybackProgress;
use Illuminate\Http\JsonResponse;

class GetPlaybackProgressController
{
    public function __construct(
        private GetPlaybackProgress $getPlaybackProgress,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(string $movieId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->getPlaybackProgress)($context->userId()->value(), $movieId);

        return new JsonResponse($response->toArray());
    }
}
