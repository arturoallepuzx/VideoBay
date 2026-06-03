<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

class CopyDetails
{
    private function __construct(
        private string $movieId,
        private string $movieTitle,
        private string $format,
        private string $condition,
        private int $priceCents,
        private int $stockAvailable,
        private bool $active,
        private ?string $posterPath = null,
    ) {}

    public static function create(
        string $movieId,
        string $movieTitle,
        string $format,
        string $condition,
        int $priceCents,
        int $stockAvailable,
        bool $active,
        ?string $posterPath = null,
    ): self {
        return new self($movieId, $movieTitle, $format, $condition, $priceCents, $stockAvailable, $active, $posterPath);
    }

    public function movieId(): string
    {
        return $this->movieId;
    }

    public function movieTitle(): string
    {
        return $this->movieTitle;
    }

    public function posterPath(): ?string
    {
        return $this->posterPath;
    }

    public function format(): string
    {
        return $this->format;
    }

    public function condition(): string
    {
        return $this->condition;
    }

    public function priceCents(): int
    {
        return $this->priceCents;
    }

    public function stockAvailable(): int
    {
        return $this->stockAvailable;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isAvailable(): bool
    {
        return $this->active && $this->stockAvailable > 0;
    }

    public function canFulfill(int $quantity): bool
    {
        return $this->isAvailable() && $this->stockAvailable >= $quantity;
    }
}
