<?php

declare(strict_types=1);

namespace App\Review\Application\ListPendingReviewReports;

use App\Review\Domain\Entity\Review;
use App\Review\Domain\Entity\ReviewReport;
use App\Review\Domain\ValueObject\UserDisplay;

final readonly class ListPendingReviewReportsResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /**
     * @param  array{items: list<ReviewReport>, total: int, page: int, totalPages: int}  $result
     * @param  array<string, Review>  $reviewsByUuid
     * @param  array<string, UserDisplay>  $displays
     */
    public static function create(array $result, array $reviewsByUuid, array $displays): self
    {
        $items = array_map(
            function (ReviewReport $report) use ($reviewsByUuid, $displays): array {
                $reportId = $report->id();
                if ($reportId === null) {
                    throw new \LogicException('Cannot list an unpersisted ReviewReport.');
                }

                $reviewUuid = $report->reviewId()->value();
                $review = $reviewsByUuid[$reviewUuid] ?? null;
                if ($review === null) {
                    throw new \LogicException(sprintf('Review "%s" for report "%d" was not found.', $reviewUuid, $reportId));
                }

                $reporter = $displays[$report->reportedByUserId()->value()] ?? null;
                $author = $displays[$review->userId()->value()] ?? null;

                return [
                    'id' => $reportId,
                    'reason' => $report->reason()->value(),
                    'status' => $report->status()->value(),
                    'created_at' => $report->createdAt()->value()->format(\DateTimeInterface::ATOM),
                    'reporter' => self::userToArray($reporter),
                    'review' => [
                        'id' => $review->id()->value(),
                        'body' => $review->body()?->value(),
                        'rating' => $review->rating()->value(),
                        'contains_spoilers' => $review->containsSpoilers(),
                        'created_at' => $review->createdAt()->value()->format(\DateTimeInterface::ATOM),
                        'author' => self::userToArray($author),
                    ],
                ];
            },
            $result['items'],
        );

        return new self(
            items: array_values($items),
            page: $result['page'],
            totalPages: $result['totalPages'],
            total: $result['total'],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function userToArray(?UserDisplay $display): ?array
    {
        if ($display === null) {
            return null;
        }

        return [
            'uuid' => $display->uuid(),
            'name' => $display->name(),
            'avatar_url' => $display->avatarUrl(),
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'page' => $this->page,
            'total_pages' => $this->totalPages,
            'total' => $this->total,
        ];
    }
}
