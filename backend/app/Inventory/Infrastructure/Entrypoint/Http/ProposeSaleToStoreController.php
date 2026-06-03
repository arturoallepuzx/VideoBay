<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\ProposeSaleToStore\ProposeSaleToStore;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\ProposeSaleToStoreRequest;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class ProposeSaleToStoreController
{
    public function __construct(
        private ProposeSaleToStore $proposeSaleToStore,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ProposeSaleToStoreRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->proposeSaleToStore)(
            $context->userId()->value(),
            $request->validated('movie_id'),
            $request->validated('title_text'),
            $request->validated('barcode'),
            $request->validated('format'),
            $request->validated('condition'),
            $request->validated('notes'),
            $request->validated('offered_price_cents'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
