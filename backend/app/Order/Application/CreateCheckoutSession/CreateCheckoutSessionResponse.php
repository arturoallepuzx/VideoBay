<?php

declare(strict_types=1);

namespace App\Order\Application\CreateCheckoutSession;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\ValueObject\CheckoutSession;

final readonly class CreateCheckoutSessionResponse
{
    private function __construct(
        public string $orderId,
        public string $checkoutUrl,
        public int $totalCents,
    ) {}

    public static function create(Order $order, CheckoutSession $session): self
    {
        return new self(
            orderId: $order->id()->value(),
            checkoutUrl: $session->url(),
            totalCents: $order->totalCents(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'checkout_url' => $this->checkoutUrl,
            'total_cents' => $this->totalCents,
        ];
    }
}
