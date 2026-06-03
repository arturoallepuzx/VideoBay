<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Streaming\Application\RecordPlaybackProgress\RecordPlaybackProgress;
use App\Streaming\Infrastructure\Entrypoint\Http\Requests\RecordPlaybackProgressRequest;
use Illuminate\Http\JsonResponse;

class RecordPlaybackProgressController
{
    public function __construct(
        private RecordPlaybackProgress $recordPlaybackProgress,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(RecordPlaybackProgressRequest $request, string $movieId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $durationSeconds = $request->validated('duration_seconds');

        ($this->recordPlaybackProgress)(
            $context->userId()->value(),
            $movieId,
            (int) $request->validated('position_seconds'),
            $durationSeconds !== null ? (int) $durationSeconds : null,
            (bool) $request->validated('completed'),
        );

        return new JsonResponse(null, 204);
    }
}
