<?php

declare(strict_types=1);

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\ValueObject\CheckoutLineItem;
use App\Order\Domain\ValueObject\CheckoutSession;
use App\Order\Domain\ValueObject\WebhookEvent;

interface CheckoutGatewayInterface
{
    /**
     * @param  list<CheckoutLineItem>  $lineItems
     */
    public function createSession(
        array $lineItems,
        string $currency,
        string $clientReferenceId,
        string $successUrl,
        string $cancelUrl,
        \DateTimeImmutable $expiresAt,
    ): CheckoutSession;

    public function parseWebhookEvent(string $payload, string $signature): WebhookEvent;
}
