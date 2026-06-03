<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

class SubtitleUserSummary
{
    private function __construct(
        private string $uuid,
        private string $name,
        private ?string $avatarUrl,
    ) {}

    public static function create(string $uuid, string $name, ?string $avatarUrl): self
    {
        $normalizedUuid = trim($uuid);
        $normalizedName = trim($name);

        if ($normalizedUuid === '' || $normalizedName === '') {
            throw new \InvalidArgumentException('Subtitle user summary requires uuid and name.');
        }

        return new self($normalizedUuid, $normalizedName, $avatarUrl);
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

    public function equals(self $other): bool
    {
        return $this->uuid === $other->uuid
            && $this->name === $other->name
            && $this->avatarUrl === $other->avatarUrl;
    }
}
