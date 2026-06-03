<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class NotificationNotFoundException extends NotFoundException
{
    public static function forUuid(Uuid $uuid): self
    {
        return new self(sprintf('Notification "%s" not found.', $uuid->value()));
    }
}
