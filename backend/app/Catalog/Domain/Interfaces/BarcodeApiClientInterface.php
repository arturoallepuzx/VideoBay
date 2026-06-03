<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Interfaces;

use App\Shared\Domain\ValueObject\BarcodeValue;

interface BarcodeApiClientInterface
{
    public function resolveTitle(BarcodeValue $barcode): ?string;
}
