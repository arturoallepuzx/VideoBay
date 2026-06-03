<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Event;

use App\Shared\Domain\ValueObject\Uuid;

class PhysicalCopyAvailableForSale
{
    private function __construct(
        private Uuid $copyId,
        private Uuid $movieId,
    ) {}

    public static function create(Uuid $copyId, Uuid $movieId): self
    {
        return new self($copyId, $movieId);
    }

    public function copyId(): Uuid
    {
        return $this->copyId;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }
}
