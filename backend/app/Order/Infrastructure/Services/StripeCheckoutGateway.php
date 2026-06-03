<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Services;

use App\Order\Domain\Exception\WebhookSignatureException;
use App\Order\Domain\Interfaces\CheckoutGatewayInterface;
use App\Order\Domain\ValueObject\CheckoutLineItem;
use App\Order\Domain\ValueObject\CheckoutSession;
use App\Order\Domain\ValueObject\WebhookEvent;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckoutGateway implements CheckoutGatewayInterface
{
    private const CHECKOUT_COMPLETED = 'checkout.session.completed';

    public function __construct(
        private string $secretKey,
        private string $webhookSecret,
    ) {}

    public function createSession(
        array $lineItems,
        string $currency,
        string $clientReferenceId,
        string $successUrl,
        string $cancelUrl,
        \DateTimeImmutable $expiresAt,
    ): CheckoutSession {
        $stripe = new StripeClient($this->secretKey);

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => array_map(
                fn (CheckoutLineItem $item): array => [
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'product_data' => ['name' => $item->name()],
                        'unit_amount' => $item->unitAmountCents(),
                    ],
                    'quantity' => $item->quantity(),
                ],
                $lineItems,
            ),
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $clientReferenceId,
            'expires_at' => $expiresAt->getTimestamp(),
        ]);

        return CheckoutSession::create((string) $session->id, (string) $session->url);
    }

    public function parseWebhookEvent(string $payload, string $signature): WebhookEvent
    {
        try {
            $event = Webhook::constructEvent($payload, $signature, $this->webhookSecret);
        } catch (SignatureVerificationException) {
            throw WebhookSignatureException::invalid();
        }

        $sessionId = null;
        $paymentIntentId = null;
        $clientReferenceId = null;

        if ($event->type === self::CHECKOUT_COMPLETED) {
            $object = $event->data->object;
            $sessionId = isset($object->id) ? (string) $object->id : null;
            $paymentIntentId = isset($object->payment_intent) ? (string) $object->payment_intent : null;
            $clientReferenceId = isset($object->client_reference_id) ? (string) $object->client_reference_id : null;
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($payload, true) ?? [];

        return WebhookEvent::create(
            (string) $event->id,
            (string) $event->type,
            $sessionId,
            $paymentIntentId,
            $clientReferenceId,
            $decoded,
        );
    }
}
