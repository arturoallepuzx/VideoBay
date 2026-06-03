<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\DeleteReview\DeleteReview;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\Response;

class DeleteReviewController
{
    public function __construct(
        private DeleteReview $deleteReview,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(string $reviewId): Response
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        ($this->deleteReview)($reviewId, $context->userId()->value(), $context->isAdmin());

        return new Response('', 204);
    }
}
