<?php

declare(strict_types=1);

namespace App\Inventory\Application\AddPhysicalCopy;

use App\Inventory\Domain\Entity\PhysicalCopy;

final readonly class AddPhysicalCopyResponse
{
    private function __construct(
        public string $id,
        public string $movieId,
        public string $movieTitle,
        public string $sku,
        public ?string $barcode,
        public string $format,
        public ?string $region,
        public string $condition,
        public ?string $coverPhotoUrl,
        public int $priceCents,
        public string $currency,
        public int $stockAvailable,
        public int $stockReserved,
        public bool $active,
    ) {}

    public static function create(PhysicalCopy $copy, string $movieTitle): self
    {
        return new self(
            id: $copy->id()->value(),
            movieId: $copy->movieId()->value(),
            movieTitle: $movieTitle,
            sku: $copy->sku()->value(),
            barcode: $copy->barcode()?->value(),
            format: $copy->format()->value(),
            region: $copy->region()?->value(),
            condition: $copy->condition()->value(),
            coverPhotoUrl: $copy->coverPhotoUrl(),
            priceCents: $copy->price()->cents(),
            currency: $copy->price()->currency(),
            stockAvailable: $copy->stockAvailable()->value(),
            stockReserved: $copy->stockReserved()->value(),
            active: $copy->isActive(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'movie_id' => $this->movieId,
            'movie_title' => $this->movieTitle,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'format' => $this->format,
            'region' => $this->region,
            'condition' => $this->condition,
            'cover_photo_url' => $this->coverPhotoUrl,
            'price_cents' => $this->priceCents,
            'currency' => $this->currency,
            'stock_available' => $this->stockAvailable,
            'stock_reserved' => $this->stockReserved,
            'active' => $this->active,
        ];
    }
}
