<?php

declare(strict_types=1);

namespace App\Shared\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface NotificationDispatcherInterface
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function sendToUser(
        Uuid $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        array $metadata = [],
    ): void;
}
