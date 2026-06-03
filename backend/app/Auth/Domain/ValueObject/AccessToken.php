<?php

declare(strict_types=1);

namespace App\Auth\Domain\ValueObject;

use App\Shared\Domain\ValueObject\DomainDateTime;

class AccessToken
{
    private string $value;

    private DomainDateTime $expiresAt;

    private function __construct(string $value, DomainDateTime $expiresAt)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Access token value cannot be empty.');
        }

        $this->value = $value;
        $this->expiresAt = $expiresAt;
    }

    public static function create(string $value, DomainDateTime $expiresAt): self
    {
        return new self($value, $expiresAt);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function expiresAt(): DomainDateTime
    {
        return $this->expiresAt;
    }
}
