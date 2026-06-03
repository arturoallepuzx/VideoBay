<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exception;

use App\Inventory\Domain\ValueObject\SaleProposalStatus;
use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\ValueObject\Uuid;

class SaleProposalAlreadyDecidedException extends ConflictException
{
    public static function forAccept(Uuid $id, SaleProposalStatus $currentStatus): self
    {
        return new self(
            sprintf(
                'Cannot accept sale proposal "%s": current status is "%s" (must be "proposed").',
                $id->value(),
                $currentStatus->value(),
            )
        );
    }

    public static function forReject(Uuid $id, SaleProposalStatus $currentStatus): self
    {
        return new self(
            sprintf(
                'Cannot reject sale proposal "%s": current status is "%s" (must be "proposed").',
                $id->value(),
                $currentStatus->value(),
            )
        );
    }
}
