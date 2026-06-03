<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ListPendingSubtitleReports;

use App\Subtitle\Domain\Entity\Subtitle;
use App\Subtitle\Domain\Entity\SubtitleReport;
use App\Subtitle\Domain\ValueObject\SubtitleMovieSummary;
use App\Subtitle\Domain\ValueObject\SubtitleUserSummary;

final readonly class ListPendingSubtitleReportsResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /**
     * @param  array{items: list<SubtitleReport>, total: int, page: int, totalPages: int}  $result
     * @param  array<string, Subtitle>  $subtitlesByUuid
     * @param  array<string, SubtitleMovieSummary>  $moviesByUuid
     * @param  array<string, SubtitleUserSummary>  $usersByUuid
     */
    public static function create(
        array $result,
        array $subtitlesByUuid,
        array $moviesByUuid,
        array $usersByUuid,
    ): self {
        $items = array_map(
            function (SubtitleReport $report) use ($subtitlesByUuid, $moviesByUuid, $usersByUuid): array {
                $id = $report->id();
                if ($id === null) {
                    throw new \LogicException('Cannot list an unpersisted SubtitleReport.');
                }

                $subtitle = $subtitlesByUuid[$report->subtitleId()->value()] ?? null;
                if ($subtitle === null) {
                    throw new \LogicException(sprintf('Subtitle "%s" for report "%d" was not found.', $report->subtitleId()->value(), $id));
                }

                $movie = $moviesByUuid[$subtitle->movieId()->value()] ?? null;
                $uploadedByUserId = $subtitle->uploadedByUserId();
                $uploadedBy = $uploadedByUserId !== null ? ($usersByUuid[$uploadedByUserId->value()] ?? null) : null;

                return [
                    'id' => $id,
                    'reason' => $report->reason()->value(),
                    'status' => $report->status()->value(),
                    'created_at' => $report->createdAt()->value()->format(\DateTimeInterface::ATOM),
                    'subtitle' => [
                        'uuid' => $subtitle->id()->value(),
                        'language' => $subtitle->language()->value(),
                        'label' => $subtitle->label()->value(),
                        'source' => $subtitle->source()->value(),
                        'provider' => $subtitle->provider(),
                        'external_id' => $subtitle->externalId(),
                        'movie' => self::movieToArray($subtitle, $movie),
                        'uploaded_by' => self::userToArray($uploadedBy),
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

    /** @return array<string, mixed> */
    private static function movieToArray(Subtitle $subtitle, ?SubtitleMovieSummary $movie): array
    {
        return [
            'uuid' => $subtitle->movieId()->value(),
            'title' => $movie?->title(),
            'poster_path' => $movie?->posterPath(),
            'release_year' => $movie?->releaseYear(),
        ];
    }

    /** @return array<string, mixed>|null */
    private static function userToArray(?SubtitleUserSummary $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'uuid' => $user->uuid(),
            'name' => $user->name(),
            'avatar_url' => $user->avatarUrl(),
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
