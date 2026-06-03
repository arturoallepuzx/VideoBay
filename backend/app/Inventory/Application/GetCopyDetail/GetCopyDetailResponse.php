<?php

declare(strict_types=1);

namespace App\Inventory\Application\GetCopyDetail;

use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\ValueObject\MovieSummary;

final readonly class GetCopyDetailResponse
{
    private function __construct(
        public string $id,
        public string $movieId,
        public ?string $movieTitle,
        public ?string $posterPath,
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
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(PhysicalCopy $copy, ?MovieSummary $movie): self
    {
        return new self(
            id: $copy->id()->value(),
            movieId: $copy->movieId()->value(),
            movieTitle: $movie?->title(),
            posterPath: $movie?->posterPath(),
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
            createdAt: $copy->createdAt()->value()->format(\DateTimeInterface::ATOM),
            updatedAt: $copy->updatedAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'movie_id' => $this->movieId,
            'movie_title' => $this->movieTitle,
            'poster_path' => $this->posterPath,
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
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
