<?php

declare(strict_types=1);

namespace App\Streaming\Application\GetPlaybackProgress;

use App\Streaming\Domain\Entity\PlaybackProgress;

final readonly class GetPlaybackProgressResponse
{
    private function __construct(
        public ?int $positionSeconds,
        public ?int $durationSeconds,
        public bool $completed,
        public ?string $updatedAt,
    ) {}

    public static function create(?PlaybackProgress $progress): self
    {
        if ($progress === null) {
            return new self(null, null, false, null);
        }

        return new self(
            positionSeconds: $progress->positionSeconds(),
            durationSeconds: $progress->durationSeconds(),
            completed: $progress->completed(),
            updatedAt: $progress->updatedAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'position_seconds' => $this->positionSeconds,
            'duration_seconds' => $this->durationSeconds,
            'completed' => $this->completed,
            'updated_at' => $this->updatedAt,
        ];
    }
}
