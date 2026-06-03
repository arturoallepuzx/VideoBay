<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Entity\SubtitleReport;

interface SubtitleReportRepositoryInterface
{
    public function findByIdForUpdate(int $id): ?SubtitleReport;

    public function findPendingByUserAndSubtitle(Uuid $subtitleId, Uuid $userId): ?SubtitleReport;

    public function create(SubtitleReport $report): SubtitleReport;

    public function update(SubtitleReport $report): void;

    /**
     * @return array{items: list<SubtitleReport>, total: int, page: int, totalPages: int}
     */
    public function listPending(int $page, int $perPage): array;
}
