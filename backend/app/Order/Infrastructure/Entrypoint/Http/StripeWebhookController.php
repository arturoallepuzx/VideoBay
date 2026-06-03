<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\HandleStripeWebhook\HandleStripeWebhook;
use App\Order\Domain\Exception\WebhookSignatureException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController
{
    public function __construct(
        private HandleStripeWebhook $handleStripeWebhook,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            ($this->handleStripeWebhook)(
                $request->getContent(),
                (string) $request->header('Stripe-Signature', ''),
            );
        } catch (WebhookSignatureException) {
            return new Response('', 400);
        }

        return new Response('', 200);
    }
}
