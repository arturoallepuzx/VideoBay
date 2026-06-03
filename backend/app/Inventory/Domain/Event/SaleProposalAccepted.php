<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Event;

use App\Shared\Domain\ValueObject\BarcodeValue;
use App\Shared\Domain\ValueObject\Uuid;

class SaleProposalAccepted
{
    private function __construct(
        private BarcodeValue $barcode,
        private Uuid $movieId,
    ) {}

    public static function create(BarcodeValue $barcode, Uuid $movieId): self
    {
        return new self($barcode, $movieId);
    }

    public function barcode(): BarcodeValue
    {
        return $this->barcode;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }
}
