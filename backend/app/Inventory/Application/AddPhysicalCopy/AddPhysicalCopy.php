<?php

declare(strict_types=1);

namespace App\Inventory\Application\AddPhysicalCopy;

use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\Event\PhysicalCopyAdded;
use App\Inventory\Domain\Event\PhysicalCopyAvailableForSale;
use App\Inventory\Domain\Exception\MovieReferenceNotFoundException;
use App\Inventory\Domain\Exception\PhysicalCopyAlreadyExistsException;
use App\Inventory\Domain\Interfaces\MovieTitleResolverInterface;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Inventory\Domain\ValueObject\RegionCode;
use App\Inventory\Domain\ValueObject\SkuCode;
use App\Inventory\Domain\ValueObject\StockQuantity;
use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\BarcodeValue;
use App\Shared\Domain\ValueObject\MoneyAmount;
use App\Shared\Domain\ValueObject\Uuid;

class AddPhysicalCopy
{
    private string $currency;

    public function __construct(
        private PhysicalCopyRepositoryInterface $physicalCopyRepository,
        private MovieTitleResolverInterface $movieTitleResolver,
        private TransactionRunnerInterface $transactionRunner,
        private DomainEventDispatcherInterface $eventDispatcher,
        SystemCurrencyProviderInterface $currencyProvider,
    ) {
        $this->currency = $currencyProvider->getCurrency();
    }

    public function __invoke(
        string $movieUuid,
        string $skuValue,
        ?string $barcodeValue,
        string $formatValue,
        ?string $regionValue,
        string $conditionValue,
        ?string $coverPhotoUrl,
        int $priceCents,
        int $stockAvailable,
    ): AddPhysicalCopyResponse {
        $movieId = Uuid::create($movieUuid);

        $titles = $this->movieTitleResolver->resolveTitles([$movieId]);
        if (! isset($titles[$movieId->value()])) {
            throw MovieReferenceNotFoundException::forUuid($movieId);
        }

        $sku = SkuCode::create($skuValue);
        if ($this->physicalCopyRepository->existsBySku($sku)) {
            throw PhysicalCopyAlreadyExistsException::forSku($sku);
        }

        $copy = PhysicalCopy::dddCreate(
            movieId: $movieId,
            sku: $sku,
            barcode: $barcodeValue !== null ? BarcodeValue::create($barcodeValue) : null,
            format: CopyFormat::create($formatValue),
            region: $regionValue !== null ? RegionCode::create($regionValue) : null,
            condition: CopyCondition::create($conditionValue),
            coverPhotoUrl: $coverPhotoUrl,
            price: MoneyAmount::create($priceCents, $this->currency),
            stockAvailable: StockQuantity::create($stockAvailable),
        );

        $this->transactionRunner->run(function () use ($copy): void {
            $this->physicalCopyRepository->create($copy);

            if ($copy->barcode() !== null) {
                $this->eventDispatcher->dispatch(PhysicalCopyAdded::create(
                    $copy->barcode(),
                    $copy->movieId(),
                ));
            }

            if ($copy->isActive() && $copy->stockAvailable()->value() > 0) {
                $this->eventDispatcher->dispatch(PhysicalCopyAvailableForSale::create(
                    $copy->id(),
                    $copy->movieId(),
                ));
            }
        });

        return AddPhysicalCopyResponse::create($copy, $titles[$movieId->value()]);
    }
}
