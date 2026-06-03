<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entity;

use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Inventory\Domain\ValueObject\RegionCode;
use App\Inventory\Domain\ValueObject\SkuCode;
use App\Inventory\Domain\ValueObject\StockQuantity;
use App\Shared\Domain\ValueObject\BarcodeValue;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\MoneyAmount;
use App\Shared\Domain\ValueObject\Uuid;

class PhysicalCopy
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private Uuid $movieId,
        private SkuCode $sku,
        private ?BarcodeValue $barcode,
        private CopyFormat $format,
        private ?RegionCode $region,
        private CopyCondition $condition,
        private ?string $coverPhotoUrl,
        private MoneyAmount $price,
        private StockQuantity $stockAvailable,
        private StockQuantity $stockReserved,
        private bool $active,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $movieId,
        SkuCode $sku,
        ?BarcodeValue $barcode,
        CopyFormat $format,
        ?RegionCode $region,
        CopyCondition $condition,
        ?string $coverPhotoUrl,
        MoneyAmount $price,
        StockQuantity $stockAvailable,
        bool $active = true,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $movieId,
            $sku,
            $barcode,
            $format,
            $region,
            $condition,
            $coverPhotoUrl,
            $price,
            $stockAvailable,
            StockQuantity::zero(),
            $active,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $movieId,
        string $sku,
        ?string $barcode,
        string $format,
        ?string $region,
        string $condition,
        ?string $coverPhotoUrl,
        int $priceCents,
        string $currency,
        int $stockAvailable,
        int $stockReserved,
        bool $active,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($movieId),
            SkuCode::create($sku),
            $barcode !== null ? BarcodeValue::create($barcode) : null,
            CopyFormat::create($format),
            $region !== null ? RegionCode::create($region) : null,
            CopyCondition::create($condition),
            $coverPhotoUrl,
            MoneyAmount::create($priceCents, $currency),
            StockQuantity::create($stockAvailable),
            StockQuantity::create($stockReserved),
            $active,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function reserveStock(int $quantity): void
    {
        $this->assertPositiveQuantity($quantity, 'reserve');

        if ($this->stockAvailable->value() < $quantity) {
            throw InsufficientStockException::forReservation(
                $this->sku,
                $quantity,
                $this->stockAvailable->value(),
            );
        }

        $this->stockAvailable = $this->stockAvailable->subtract($quantity);
        $this->stockReserved = $this->stockReserved->add($quantity);
        $this->touch();
    }

    public function releaseReservedStock(int $quantity): void
    {
        $this->assertPositiveQuantity($quantity, 'release');

        if ($this->stockReserved->value() < $quantity) {
            throw InsufficientStockException::forRelease(
                $this->sku,
                $quantity,
                $this->stockReserved->value(),
            );
        }

        $this->stockReserved = $this->stockReserved->subtract($quantity);
        $this->stockAvailable = $this->stockAvailable->add($quantity);
        $this->touch();
    }

    public function confirmStockSale(int $quantity): void
    {
        $this->assertPositiveQuantity($quantity, 'confirm');

        if ($this->stockReserved->value() < $quantity) {
            throw InsufficientStockException::forSaleConfirmation(
                $this->sku,
                $quantity,
                $this->stockReserved->value(),
            );
        }

        $this->stockReserved = $this->stockReserved->subtract($quantity);
        $this->touch();
    }

    public function restock(int $quantity): void
    {
        $this->assertPositiveQuantity($quantity, 'restock');

        $this->stockAvailable = $this->stockAvailable->add($quantity);
        $this->touch();
    }

    public function updateBarcode(?BarcodeValue $barcode): void
    {
        if ($this->barcode === null && $barcode === null) {
            return;
        }

        if ($this->barcode !== null && $barcode !== null && $this->barcode->equals($barcode)) {
            return;
        }

        $this->barcode = $barcode;
        $this->touch();
    }

    public function updateCondition(CopyCondition $condition): void
    {
        if ($this->condition->equals($condition)) {
            return;
        }

        $this->condition = $condition;
        $this->touch();
    }

    public function updatePrice(MoneyAmount $price): void
    {
        if ($this->price->equals($price)) {
            return;
        }

        $this->price = $price;
        $this->touch();
    }

    public function updateCoverPhotoUrl(?string $coverPhotoUrl): void
    {
        if ($this->coverPhotoUrl === $coverPhotoUrl) {
            return;
        }

        $this->coverPhotoUrl = $coverPhotoUrl;
        $this->touch();
    }

    public function updateActive(bool $active): void
    {
        if ($this->active === $active) {
            return;
        }

        $this->active = $active;
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function sku(): SkuCode
    {
        return $this->sku;
    }

    public function barcode(): ?BarcodeValue
    {
        return $this->barcode;
    }

    public function format(): CopyFormat
    {
        return $this->format;
    }

    public function region(): ?RegionCode
    {
        return $this->region;
    }

    public function condition(): CopyCondition
    {
        return $this->condition;
    }

    public function coverPhotoUrl(): ?string
    {
        return $this->coverPhotoUrl;
    }

    public function price(): MoneyAmount
    {
        return $this->price;
    }

    public function stockAvailable(): StockQuantity
    {
        return $this->stockAvailable;
    }

    public function stockReserved(): StockQuantity
    {
        return $this->stockReserved;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    private function assertPositiveQuantity(int $quantity, string $operation): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException(
                sprintf('Stock %s quantity must be positive, got %d.', $operation, $quantity)
            );
        }
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
