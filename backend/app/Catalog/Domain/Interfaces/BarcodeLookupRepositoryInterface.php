<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Interfaces;

use App\Catalog\Domain\Entity\BarcodeLookup;
use App\Shared\Domain\ValueObject\BarcodeValue;

interface BarcodeLookupRepositoryInterface
{
    public function findByBarcode(BarcodeValue $barcode): ?BarcodeLookup;

    public function upsert(BarcodeLookup $lookup): void;
}
