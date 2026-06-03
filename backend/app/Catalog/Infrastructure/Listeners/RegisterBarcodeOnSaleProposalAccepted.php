<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Listeners;

use App\Catalog\Domain\Entity\BarcodeLookup;
use App\Catalog\Domain\Interfaces\BarcodeLookupRepositoryInterface;
use App\Catalog\Domain\ValueObject\BarcodeResolutionMethod;
use App\Inventory\Domain\Event\SaleProposalAccepted;

class RegisterBarcodeOnSaleProposalAccepted
{
    public function __construct(
        private BarcodeLookupRepositoryInterface $barcodeLookupRepository,
    ) {}

    public function handle(SaleProposalAccepted $event): void
    {
        $lookup = BarcodeLookup::dddCreate(
            $event->barcode(),
            $event->movieId(),
            BarcodeResolutionMethod::saleProposal(),
        );

        $this->barcodeLookupRepository->upsert($lookup);
    }
}
