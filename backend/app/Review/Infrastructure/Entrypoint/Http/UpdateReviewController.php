<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\UpdateReview\UpdateReview;
use App\Review\Infrastructure\Entrypoint\Http\Requests\UpdateReviewRequest;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class UpdateReviewController
{
    public function __construct(
        private UpdateReview $updateReview,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(UpdateReviewRequest $request, string $reviewId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $rating = $request->has('rating') ? (int) $request->validated('rating') : null;
        $bodyProvided = $request->has('body');
        $body = $bodyProvided ? $request->validated('body') : null;
        $containsSpoilers = $request->has('contains_spoilers') ? (bool) $request->validated('contains_spoilers') : null;

        $response = ($this->updateReview)(
            $reviewId,
            $context->userId()->value(),
            $rating,
            $body,
            $bodyProvided,
            $containsSpoilers,
        );

        return new JsonResponse($response->toArray());
    }
}
