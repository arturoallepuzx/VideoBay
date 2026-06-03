<?php

declare(strict_types=1);

namespace App\Streaming\Application\ListVideoFilesForAdmin;

use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\ValueObject\MovieSummary;

final readonly class ListVideoFilesForAdminResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /**
     * @param  array{items: list<VideoFile>, total: int, page: int, totalPages: int}  $result
     * @param  array<string, MovieSummary>  $movieSummaries
     */
    public static function create(array $result, array $movieSummaries): self
    {
        $items = array_map(
            fn (VideoFile $vf): array => self::videoFileToArray($vf, $movieSummaries[$vf->movieId()->value()] ?? null),
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
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'page' => $this->page,
            'total_pages' => $this->totalPages,
            'total' => $this->total,
        ];
    }

    /** @return array<string, mixed> */
    private static function videoFileToArray(VideoFile $vf, ?MovieSummary $movieSummary): array
    {
        $movieId = $vf->movieId()->value();

        return [
            'id' => $vf->id()->value(),
            'movie_id' => $movieId,
            'movie' => [
                'id' => $movieSummary?->uuid() ?? $movieId,
                'title' => $movieSummary?->title(),
                'poster_path' => $movieSummary?->posterPath(),
                'release_year' => $movieSummary?->releaseYear(),
            ],
            'original_filename' => $vf->originalFilename(),
            'processed_path' => $vf->processedPath(),
            'duration_seconds' => $vf->durationSeconds(),
            'file_size_bytes' => $vf->fileSizeBytes(),
            'audio_language' => $vf->audioLanguage(),
            'processing_status' => $vf->processingStatus()->value(),
            'processing_error' => $vf->processingError(),
            'created_at' => $vf->createdAt()->value()->format(\DateTimeInterface::ATOM),
            'deleted_at' => $vf->deletedAt()?->value()->format(\DateTimeInterface::ATOM),
        ];
    }
}
