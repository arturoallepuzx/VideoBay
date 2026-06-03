<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;
use PHPUnit\Framework\TestCase;

class VideoFileTest extends TestCase
{
    private function makePending(): VideoFile
    {
        return VideoFile::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            'inception.mkv',
            'mkv',
            '/videos/originals/x.mkv',
            null,
            null,
            null,
            null,
            VideoFileProcessingStatus::pending(),
        );
    }

    private function makeReady(): VideoFile
    {
        return VideoFile::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            null,
            null,
            null,
            '/videos/x.mp4',
            8820,
            4421532000,
            'en',
            VideoFileProcessingStatus::ready(),
        );
    }

    public function test_ddd_create_ready_requires_processed_path_and_metadata(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VideoFile::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            VideoFileProcessingStatus::ready(),
        );
    }

    public function test_ddd_create_pending_requires_original_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VideoFile::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            'x.mkv',
            'mkv',
            null,
            null,
            null,
            null,
            null,
            VideoFileProcessingStatus::pending(),
        );
    }

    public function test_pending_is_not_ready(): void
    {
        $videoFile = $this->makePending();

        $this->assertTrue($videoFile->processingStatus()->isPending());
        $this->assertFalse($videoFile->isReady());
    }

    public function test_full_transcoding_happy_path(): void
    {
        $videoFile = $this->makePending();

        $videoFile->markProcessing();
        $this->assertTrue($videoFile->processingStatus()->isProcessing());

        $videoFile->markReady('/videos/uuid.mp4', 8820, 4421532000, 'en');

        $this->assertTrue($videoFile->isReady());
        $this->assertSame('/videos/uuid.mp4', $videoFile->processedPath());
        $this->assertSame(8820, $videoFile->durationSeconds());
        $this->assertSame('/videos/originals/x.mkv', $videoFile->originalPath());
    }

    public function test_clear_original_reference_after_ready(): void
    {
        $videoFile = $this->makePending();
        $videoFile->markProcessing();
        $videoFile->markReady('/videos/uuid.mp4', 100, 200, null);

        $videoFile->clearOriginalReference();

        $this->assertNull($videoFile->originalPath());
        $this->assertTrue($videoFile->isReady());
    }

    public function test_mark_failed_records_error(): void
    {
        $videoFile = $this->makePending();
        $videoFile->markProcessing();

        $videoFile->markFailed('ffmpeg exploded');

        $this->assertTrue($videoFile->processingStatus()->isFailed());
        $this->assertSame('ffmpeg exploded', $videoFile->processingError());
        $this->assertFalse($videoFile->isReady());
    }

    public function test_soft_delete_makes_not_ready(): void
    {
        $videoFile = $this->makeReady();

        $this->assertTrue($videoFile->isReady());

        $videoFile->softDelete();

        $this->assertTrue($videoFile->isDeleted());
        $this->assertFalse($videoFile->isReady());
    }

    public function test_reassign_to_movie_changes_movie_id(): void
    {
        $videoFile = $this->makeReady();
        $newMovieId = Uuid::generate();

        $videoFile->reassignToMovie($newMovieId);

        $this->assertTrue($videoFile->movieId()->equals($newMovieId));
        $this->assertTrue($videoFile->wasModified());
    }
}
