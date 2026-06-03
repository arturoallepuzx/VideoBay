<?php

declare(strict_types=1);

namespace App\Review\Domain\ValueObject;

class UserDisplay
{
    private function __construct(
        private string $uuid,
        private string $name,
        private ?string $avatarUrl,
    ) {}

    public static function create(string $uuid, string $name, ?string $avatarUrl): self
    {
        return new self($uuid, $name, $avatarUrl);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatarUrl;
    }
}
