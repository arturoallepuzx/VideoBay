<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

class CheckoutSession
{
    private function __construct(
        private string $sessionId,
        private string $url,
    ) {}

    public static function create(string $sessionId, string $url): self
    {
        return new self($sessionId, $url);
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function url(): string
    {
        return $this->url;
    }
}
