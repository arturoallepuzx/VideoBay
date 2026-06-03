<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ReportSubtitle;

use App\Subtitle\Domain\Entity\SubtitleReport;

final readonly class ReportSubtitleResponse
{
    private function __construct(
        public int $id,
        public string $subtitleId,
        public string $reason,
        public string $status,
    ) {}

    public static function create(SubtitleReport $report): self
    {
        $id = $report->id();

        if ($id === null) {
            throw new \LogicException('Cannot build response for unpersisted SubtitleReport.');
        }

        return new self(
            id: $id,
            subtitleId: $report->subtitleId()->value(),
            reason: $report->reason()->value(),
            status: $report->status()->value(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subtitle_id' => $this->subtitleId,
            'reason' => $this->reason,
            'status' => $this->status,
        ];
    }
}
