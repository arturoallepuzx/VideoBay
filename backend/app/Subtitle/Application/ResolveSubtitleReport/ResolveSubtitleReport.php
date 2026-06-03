<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ResolveSubtitleReport;

use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Subtitle\Domain\Event\SubtitleRemovedByModeration;
use App\Subtitle\Domain\Exception\InvalidSubtitleReportDecisionException;
use App\Subtitle\Domain\Exception\SubtitleReportNotFoundException;
use App\Subtitle\Domain\Interfaces\SubtitleReportRepositoryInterface;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;

class ResolveSubtitleReport
{
    private const DECISION_RESOLVED = 'resolved';

    private const DECISION_DISMISSED = 'dismissed';

    public function __construct(
        private SubtitleReportRepositoryInterface $reportRepository,
        private SubtitleRepositoryInterface $subtitleRepository,
        private TransactionRunnerInterface $transactionRunner,
        private DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(int $reportId, string $decision): ResolveSubtitleReportResponse
    {
        if ($decision !== self::DECISION_RESOLVED && $decision !== self::DECISION_DISMISSED) {
            throw InvalidSubtitleReportDecisionException::forValue($decision);
        }

        $report = $this->transactionRunner->run(function () use ($reportId, $decision) {
            $report = $this->reportRepository->findByIdForUpdate($reportId);

            if ($report === null) {
                throw SubtitleReportNotFoundException::forId($reportId);
            }

            if ($decision === self::DECISION_RESOLVED) {
                $report->resolve();

                $subtitle = $this->subtitleRepository->findByUuidForUpdate($report->subtitleId());
                if ($subtitle !== null && ! $subtitle->isDeleted()) {
                    $uploadedByUserId = $subtitle->uploadedByUserId();
                    if ($uploadedByUserId !== null) {
                        $this->eventDispatcher->dispatch(SubtitleRemovedByModeration::create(
                            $subtitle->id(),
                            $uploadedByUserId,
                            $subtitle->movieId(),
                            $subtitle->language(),
                            $subtitle->label(),
                        ));
                    }

                    $subtitle->softDelete();
                    if ($subtitle->wasModified()) {
                        $this->subtitleRepository->update($subtitle);
                    }
                }
            } else {
                $report->dismiss();
            }

            if ($report->wasModified()) {
                $this->reportRepository->update($report);
            }

            return $report;
        });

        return ResolveSubtitleReportResponse::create($report);
    }
}
