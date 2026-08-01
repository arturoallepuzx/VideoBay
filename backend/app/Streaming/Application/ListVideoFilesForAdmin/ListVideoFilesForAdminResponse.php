<?php

declare(strict_types=1);

namespace App\Streaming\Application\ListVideoFilesForAdmin;

use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\ValueObject\MovieSummary;
use App\Streaming\Domain\ValueObject\StoragePrice;

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
    public static function create(array $result, array $movieSummaries, ?StoragePrice $price): self
    {
        $items = array_map(
            fn (VideoFile $vf): array => self::videoFileToArray($vf, $movieSummaries[$vf->movieId()->value()] ?? null, $price),
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
    private static function monthlyCostOf(VideoFile $videoFile, ?StoragePrice $price): ?float
    {
        $metadata = $videoFile->metadata();
        $location = $videoFile->processedLocation();

        if ($price === null || $metadata === null || $location === null || ! $location->disk()->isR2()) {
            return null;
        }

        return $price->monthlyCostFor($metadata->fileSizeBytes());
    }

    private static function videoFileToArray(VideoFile $vf, ?MovieSummary $movieSummary, ?StoragePrice $price): array
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
            'storage_disk' => ($vf->processedLocation() ?? $vf->originalLocation())?->disk()->value(),
            'duration_seconds' => $vf->metadata()?->durationSeconds(),
            'file_size_bytes' => $vf->metadata()?->fileSizeBytes(),
            'audio_language' => $vf->metadata()?->audioLanguage(),
            'monthly_cost' => self::monthlyCostOf($vf, $price),
            'processing_status' => $vf->processingStatus()->value(),
            'processing_error' => $vf->processingError(),
            'created_at' => $vf->createdAt()->value()->format(\DateTimeInterface::ATOM),
            'deleted_at' => $vf->deletedAt()?->value()->format(\DateTimeInterface::ATOM),
        ];
    }
}
