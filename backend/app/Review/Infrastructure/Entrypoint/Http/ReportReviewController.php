<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\ReportReview\ReportReview;
use App\Review\Infrastructure\Entrypoint\Http\Requests\ReportReviewRequest;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class ReportReviewController
{
    public function __construct(
        private ReportReview $reportReview,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ReportReviewRequest $request, string $reviewId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->reportReview)(
            $reviewId,
            $context->userId()->value(),
            (string) $request->validated('reason'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
