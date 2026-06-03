<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class SaleProposalNotFoundException extends NotFoundException
{
    public static function forUuid(Uuid $uuid): self
    {
        return new self(sprintf('Sale proposal "%s" not found.', $uuid->value()));
    }
}
