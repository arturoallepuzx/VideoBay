<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Exception;

use App\Shared\Domain\Exception\ValidationException;

class InvalidPinnedSlotsException extends ValidationException
{
    public static function tooManySlots(int $given, int $max): self
    {
        return new self(sprintf('Too many pinned slots: %d (max %d).', $given, $max));
    }

    public static function positionOutOfRange(int $position, int $max): self
    {
        return new self(sprintf('Pinned position %d is out of range [1..%d].', $position, $max));
    }

    public static function duplicatePosition(int $position): self
    {
        return new self(sprintf('Duplicate pinned position %d.', $position));
    }

    public static function duplicateMovie(string $movieUuid): self
    {
        return new self(sprintf('Movie %s cannot be pinned in two slots.', $movieUuid));
    }

    public static function movieNotFound(string $movieUuid): self
    {
        return new self(sprintf('Movie %s does not exist.', $movieUuid));
    }
}
