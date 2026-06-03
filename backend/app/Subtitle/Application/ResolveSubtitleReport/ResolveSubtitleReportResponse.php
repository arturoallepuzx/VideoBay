<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ResolveSubtitleReport;

use App\Subtitle\Domain\Entity\SubtitleReport;

final readonly class ResolveSubtitleReportResponse
{
    private function __construct(
        public int $id,
        public string $status,
    ) {}

    public static function create(SubtitleReport $report): self
    {
        $id = $report->id();

        if ($id === null) {
            throw new \LogicException('Cannot build response for unpersisted SubtitleReport.');
        }

        return new self($id, $report->status()->value());
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
        ];
    }
}
