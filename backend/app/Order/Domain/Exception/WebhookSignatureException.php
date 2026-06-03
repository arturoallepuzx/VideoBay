<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

class WebhookSignatureException extends \RuntimeException
{
    public static function invalid(): self
    {
        return new self('Invalid Stripe webhook signature.');
    }
}
