<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Streaming\Application\RemovePlaybackProgress\RemovePlaybackProgress;
use Illuminate\Http\JsonResponse;

class RemovePlaybackProgressController
{
    public function __construct(
        private RemovePlaybackProgress $removePlaybackProgress,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(string $movieId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        ($this->removePlaybackProgress)($context->userId()->value(), $movieId);

        return new JsonResponse(null, 204);
    }
}
