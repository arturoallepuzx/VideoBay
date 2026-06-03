<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        private string $id,
        private string $type,
        private ?string $sessionId,
        private ?string $paymentIntentId,
        private ?string $clientReferenceId,
        private array $payload,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function create(
        string $id,
        string $type,
        ?string $sessionId,
        ?string $paymentIntentId,
        ?string $clientReferenceId,
        array $payload,
    ): self {
        return new self($id, $type, $sessionId, $paymentIntentId, $clientReferenceId, $payload);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function sessionId(): ?string
    {
        return $this->sessionId;
    }

    public function paymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }

    public function clientReferenceId(): ?string
    {
        return $this->clientReferenceId;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
