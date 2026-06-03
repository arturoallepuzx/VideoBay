<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ReportSubtitle;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Entity\SubtitleReport;
use App\Subtitle\Domain\Exception\SubtitleNotFoundException;
use App\Subtitle\Domain\Interfaces\SubtitleReportRepositoryInterface;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;
use App\Subtitle\Domain\ValueObject\SubtitleReportReason;

class ReportSubtitle
{
    public function __construct(
        private SubtitleRepositoryInterface $subtitleRepository,
        private SubtitleReportRepositoryInterface $reportRepository,
    ) {}

    public function __invoke(string $subtitleUuid, string $reporterUuid, string $reason): ReportSubtitleResponse
    {
        $subtitleId = Uuid::create($subtitleUuid);
        $reporterId = Uuid::create($reporterUuid);
        $subtitle = $this->subtitleRepository->findByUuid($subtitleId);

        if ($subtitle === null || $subtitle->isDeleted()) {
            throw SubtitleNotFoundException::forUuid($subtitleId);
        }

        $existing = $this->reportRepository->findPendingByUserAndSubtitle($subtitleId, $reporterId);
        if ($existing !== null) {
            return ReportSubtitleResponse::create($existing);
        }

        $report = SubtitleReport::dddCreate($subtitleId, $reporterId, SubtitleReportReason::create($reason));
        $persisted = $this->reportRepository->create($report);

        return ReportSubtitleResponse::create($persisted);
    }
}
