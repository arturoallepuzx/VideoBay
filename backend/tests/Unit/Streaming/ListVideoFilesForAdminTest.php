<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Application\ListVideoFilesForAdmin\ListVideoFilesForAdmin;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Interfaces\MovieSummaryResolverInterface;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\ValueObject\MovieSummary;
use Mockery;
use Tests\TestCase;

class ListVideoFilesForAdminTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_lists_video_files_with_movie_summary(): void
    {
        $movieId = Uuid::generate();
        $videoFile = $this->videoFile($movieId);

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('listForAdmin')
            ->once()
            ->with(1, 20)
            ->andReturn([
                'items' => [$videoFile],
                'total' => 1,
                'page' => 1,
                'totalPages' => 1,
            ]);

        $movieSummaryResolver = Mockery::mock(MovieSummaryResolverInterface::class);
        $movieSummaryResolver->shouldReceive('resolveMany')
            ->once()
            ->with(Mockery::on(fn (array $ids): bool => count($ids) === 1 && $ids[0]->equals($movieId)))
            ->andReturn([
                $movieId->value() => MovieSummary::create($movieId, 'The Matrix', '/matrix.jpg', 1999),
            ]);

        $response = (new ListVideoFilesForAdmin($repository, $movieSummaryResolver))(1, 20);

        $this->assertSame([
            'items' => [
                [
                    'id' => $videoFile->id()->value(),
                    'movie_id' => $movieId->value(),
                    'movie' => [
                        'id' => $movieId->value(),
                        'title' => 'The Matrix',
                        'poster_path' => '/matrix.jpg',
                        'release_year' => 1999,
                    ],
                    'original_filename' => 'source.mkv',
                    'processed_path' => '/videos/ready.mp4',
                    'duration_seconds' => 120,
                    'file_size_bytes' => 4096,
                    'audio_language' => 'en',
                    'processing_status' => 'ready',
                    'processing_error' => null,
                    'created_at' => '2026-04-25T10:00:00+00:00',
                    'deleted_at' => null,
                ],
            ],
            'page' => 1,
            'total_pages' => 1,
            'total' => 1,
        ], $response->toArray());
    }

    private function videoFile(Uuid $movieId): VideoFile
    {
        $createdAt = new \DateTimeImmutable('2026-04-25T10:00:00+00:00');

        return VideoFile::fromPersistence(
            Uuid::generate()->value(),
            $movieId->value(),
            'source.mkv',
            'mkv',
            '/videos/originals/source.mkv',
            '/videos/ready.mp4',
            120,
            4096,
            'en',
            'ready',
            null,
            $createdAt,
            $createdAt,
            null,
        );
    }
}
