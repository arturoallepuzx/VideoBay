<?php

declare(strict_types=1);

namespace App\Inventory\Application\ProposeSaleToStore;

use App\Inventory\Domain\Entity\SaleProposal;
use App\Inventory\Domain\Exception\MovieReferenceNotFoundException;
use App\Inventory\Domain\Interfaces\MovieTitleResolverInterface;
use App\Inventory\Domain\Interfaces\SaleProposalRepositoryInterface;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;
use App\Shared\Domain\ValueObject\BarcodeValue;
use App\Shared\Domain\ValueObject\MoneyAmount;
use App\Shared\Domain\ValueObject\Uuid;

class ProposeSaleToStore
{
    private string $currency;

    public function __construct(
        private SaleProposalRepositoryInterface $saleProposalRepository,
        private MovieTitleResolverInterface $movieTitleResolver,
        SystemCurrencyProviderInterface $currencyProvider,
    ) {
        $this->currency = $currencyProvider->getCurrency();
    }

    public function __invoke(
        string $userUuid,
        ?string $movieUuid,
        ?string $titleText,
        ?string $barcodeValue,
        string $formatValue,
        string $conditionValue,
        ?string $notes,
        ?int $offeredPriceCents,
    ): ProposeSaleToStoreResponse {
        $trimmedTitle = $titleText !== null ? trim($titleText) : null;
        $trimmedNotes = $notes !== null ? trim($notes) : null;

        if ($movieUuid === null && ($trimmedTitle === null || $trimmedTitle === '') && $barcodeValue === null) {
            throw new \InvalidArgumentException(
                'Sale proposal requires at least one identifying field: movieUuid, titleText or barcode.'
            );
        }

        $movieId = null;
        if ($movieUuid !== null) {
            $movieId = Uuid::create($movieUuid);
            $titles = $this->movieTitleResolver->resolveTitles([$movieId]);
            if (! isset($titles[$movieId->value()])) {
                throw MovieReferenceNotFoundException::forUuid($movieId);
            }
        }

        $proposal = SaleProposal::dddCreate(
            userId: Uuid::create($userUuid),
            movieId: $movieId,
            titleText: $trimmedTitle === '' ? null : $trimmedTitle,
            barcode: $barcodeValue !== null ? BarcodeValue::create($barcodeValue) : null,
            format: CopyFormat::create($formatValue),
            condition: CopyCondition::create($conditionValue),
            notes: $trimmedNotes === '' ? null : $trimmedNotes,
            offeredPrice: $offeredPriceCents !== null ? MoneyAmount::create($offeredPriceCents, $this->currency) : null,
        );

        $this->saleProposalRepository->create($proposal);

        return ProposeSaleToStoreResponse::create($proposal);
    }
}
