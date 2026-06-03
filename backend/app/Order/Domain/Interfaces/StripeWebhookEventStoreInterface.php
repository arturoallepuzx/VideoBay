<?php

declare(strict_types=1);

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\ValueObject\WebhookEvent;

interface StripeWebhookEventStoreInterface
{
    public function shouldProcess(WebhookEvent $event): bool;

    public function markProcessed(string $eventId): void;

    public function markFailed(string $eventId, string $error): void;
}
