<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\ReviewSaleProposal\ReviewSaleProposal;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\ReviewSaleProposalRequest;
use Illuminate\Http\JsonResponse;

class ReviewSaleProposalController
{
    public function __construct(
        private ReviewSaleProposal $reviewSaleProposal,
    ) {}

    public function __invoke(ReviewSaleProposalRequest $request, string $proposalId): JsonResponse
    {
        $response = ($this->reviewSaleProposal)(
            $proposalId,
            $request->validated('decision'),
        );

        return new JsonResponse($response->toArray());
    }
}
