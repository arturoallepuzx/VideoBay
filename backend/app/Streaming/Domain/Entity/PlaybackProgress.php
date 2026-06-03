<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class PlaybackProgress
{
    private bool $modified = false;

    private function __construct(
        private Uuid $userId,
        private Uuid $movieId,
        private int $positionSeconds,
        private ?int $durationSeconds,
        private bool $completed,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $userId,
        Uuid $movieId,
        int $positionSeconds,
        ?int $durationSeconds,
        bool $completed,
    ): self {
        return new self(
            $userId,
            $movieId,
            max(0, $positionSeconds),
            $durationSeconds,
            $completed,
            DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $userId,
        string $movieId,
        int $positionSeconds,
        ?int $durationSeconds,
        bool $completed,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($userId),
            Uuid::create($movieId),
            $positionSeconds,
            $durationSeconds,
            $completed,
            DomainDateTime::create($updatedAt),
        );
    }

    public function reportPosition(int $positionSeconds, ?int $durationSeconds, bool $completed): void
    {
        $newPosition = max(0, $positionSeconds);

        if ($this->positionSeconds === $newPosition
            && $this->durationSeconds === $durationSeconds
            && $this->completed === $completed) {
            return;
        }

        $this->positionSeconds = $newPosition;
        $this->durationSeconds = $durationSeconds;
        $this->completed = $completed;
        $this->updatedAt = DomainDateTime::now();
        $this->modified = true;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function positionSeconds(): int
    {
        return $this->positionSeconds;
    }

    public function durationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function completed(): bool
    {
        return $this->completed;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }
}
