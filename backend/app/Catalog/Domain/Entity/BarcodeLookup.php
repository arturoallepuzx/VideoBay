<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\ValueObject\BarcodeResolutionMethod;
use App\Shared\Domain\ValueObject\BarcodeValue;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class BarcodeLookup
{
    private function __construct(
        private BarcodeValue $barcode,
        private ?Uuid $movieId,
        private BarcodeResolutionMethod $resolvedVia,
        private bool $confirmed,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        BarcodeValue $barcode,
        ?Uuid $movieId,
        BarcodeResolutionMethod $resolvedVia,
        bool $confirmed = true,
    ): self {
        $now = DomainDateTime::now();

        return new self($barcode, $movieId, $resolvedVia, $confirmed, $now, $now);
    }

    public static function fromPersistence(
        string $barcode,
        ?string $movieId,
        string $resolvedVia,
        bool $confirmed,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            BarcodeValue::create($barcode),
            $movieId !== null ? Uuid::create($movieId) : null,
            BarcodeResolutionMethod::create($resolvedVia),
            $confirmed,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function barcode(): BarcodeValue
    {
        return $this->barcode;
    }

    public function movieId(): ?Uuid
    {
        return $this->movieId;
    }

    public function resolvedVia(): BarcodeResolutionMethod
    {
        return $this->resolvedVia;
    }

    public function confirmed(): bool
    {
        return $this->confirmed;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }
}
