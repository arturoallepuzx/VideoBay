<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\CreateReview\CreateReview;
use App\Review\Infrastructure\Entrypoint\Http\Requests\CreateReviewRequest;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class CreateReviewController
{
    public function __construct(
        private CreateReview $createReview,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(CreateReviewRequest $request, string $movieId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->createReview)(
            $context->userId()->value(),
            $movieId,
            (int) $request->validated('rating'),
            $request->validated('body'),
            (bool) $request->validated('contains_spoilers'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
