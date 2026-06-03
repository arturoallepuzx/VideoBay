<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\ToggleReviewLike\ToggleReviewLike;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class ToggleReviewLikeController
{
    public function __construct(
        private ToggleReviewLike $toggleReviewLike,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(string $reviewId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->toggleReviewLike)($reviewId, $context->userId()->value());

        return new JsonResponse($response->toArray());
    }
}
