<?php

declare(strict_types=1);

namespace App\Inventory\Application\UpdatePhysicalCopy;

use App\Inventory\Domain\Exception\PhysicalCopyNotFoundException;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;
use App\Shared\Domain\ValueObject\BarcodeValue;
use App\Shared\Domain\ValueObject\MoneyAmount;
use App\Shared\Domain\ValueObject\Uuid;

class UpdatePhysicalCopy
{
    private string $currency;

    public function __construct(
        private PhysicalCopyRepositoryInterface $physicalCopyRepository,
        SystemCurrencyProviderInterface $currencyProvider,
    ) {
        $this->currency = $currencyProvider->getCurrency();
    }

    public function __invoke(
        string $copyUuid,
        ?string $barcode,
        bool $barcodeWasProvided,
        ?string $condition,
        ?string $coverPhotoUrl,
        bool $coverPhotoUrlWasProvided,
        ?int $priceCents,
        ?bool $active,
    ): UpdatePhysicalCopyResponse {
        $uuid = Uuid::create($copyUuid);
        $copy = $this->physicalCopyRepository->findByUuid($uuid);

        if ($copy === null) {
            throw PhysicalCopyNotFoundException::forUuid($uuid);
        }

        if ($barcodeWasProvided) {
            $copy->updateBarcode($barcode !== null ? BarcodeValue::create($barcode) : null);
        }

        if ($condition !== null) {
            $copy->updateCondition(CopyCondition::create($condition));
        }

        if ($coverPhotoUrlWasProvided) {
            $copy->updateCoverPhotoUrl($coverPhotoUrl);
        }

        if ($priceCents !== null) {
            $copy->updatePrice(MoneyAmount::create($priceCents, $this->currency));
        }

        if ($active !== null) {
            $copy->updateActive($active);
        }

        if ($copy->wasModified()) {
            $this->physicalCopyRepository->update($copy);
        }

        return UpdatePhysicalCopyResponse::create($copy);
    }
}
