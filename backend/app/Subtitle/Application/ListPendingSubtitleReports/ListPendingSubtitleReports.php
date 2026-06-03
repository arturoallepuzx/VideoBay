<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ListPendingSubtitleReports;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Entity\Subtitle;
use App\Subtitle\Domain\Entity\SubtitleReport;
use App\Subtitle\Domain\Interfaces\SubtitleMovieSummaryResolverInterface;
use App\Subtitle\Domain\Interfaces\SubtitleReportRepositoryInterface;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;
use App\Subtitle\Domain\Interfaces\SubtitleUserSummaryResolverInterface;

class ListPendingSubtitleReports
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private SubtitleReportRepositoryInterface $reportRepository,
        private SubtitleRepositoryInterface $subtitleRepository,
        private SubtitleMovieSummaryResolverInterface $movieSummaryResolver,
        private SubtitleUserSummaryResolverInterface $userSummaryResolver,
    ) {}

    public function __invoke(int $page, int $perPage): ListPendingSubtitleReportsResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));
        $result = $this->reportRepository->listPending($page, $perPage);
        $subtitlesByUuid = $this->subtitleRepository->findManyByUuids($this->subtitleIds($result['items']));

        return ListPendingSubtitleReportsResponse::create(
            $result,
            $subtitlesByUuid,
            $this->movieSummaryResolver->resolveMany($this->movieIds($subtitlesByUuid)),
            $this->userSummaryResolver->resolveMany($this->uploadedByUserIds($subtitlesByUuid)),
        );
    }

    /**
     * @param  list<SubtitleReport>  $reports
     * @return list<Uuid>
     */
    private function subtitleIds(array $reports): array
    {
        $seen = [];
        $ids = [];

        foreach ($reports as $report) {
            $value = $report->subtitleId()->value();
            if (! isset($seen[$value])) {
                $seen[$value] = true;
                $ids[] = $report->subtitleId();
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, Subtitle>  $subtitlesByUuid
     * @return list<Uuid>
     */
    private function movieIds(array $subtitlesByUuid): array
    {
        $seen = [];
        $ids = [];

        foreach ($subtitlesByUuid as $subtitle) {
            $value = $subtitle->movieId()->value();
            if (! isset($seen[$value])) {
                $seen[$value] = true;
                $ids[] = $subtitle->movieId();
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, Subtitle>  $subtitlesByUuid
     * @return list<Uuid>
     */
    private function uploadedByUserIds(array $subtitlesByUuid): array
    {
        $seen = [];
        $ids = [];

        foreach ($subtitlesByUuid as $subtitle) {
            $uploadedByUserId = $subtitle->uploadedByUserId();

            if ($uploadedByUserId === null) {
                continue;
            }

            $value = $uploadedByUserId->value();
            if (! isset($seen[$value])) {
                $seen[$value] = true;
                $ids[] = $uploadedByUserId;
            }
        }

        return $ids;
    }
}
