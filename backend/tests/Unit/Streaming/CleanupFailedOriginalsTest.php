<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Application\CleanupFailedOriginals\CleanupFailedOriginals;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use Mockery;
use Tests\TestCase;

class CleanupFailedOriginalsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function failedVideo(?string $originalPath): VideoFile
    {
        $now = new \DateTimeImmutable;

        return VideoFile::fromPersistence(
            Uuid::generate()->value(),
            Uuid::generate()->value(),
            'source.mkv',
            'mkv',
            $originalPath,
            null,
            null,
            null,
            null,
            'failed',
            'ffmpeg failed',
            $now,
            $now,
            null,
        );
    }

    public function test_deletes_original_and_clears_reference_for_each_failed(): void
    {
        $first = $this->failedVideo('/videos/originals/a.mkv');
        $second = $this->failedVideo('/videos/originals/b.mkv');

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findFailedOlderThan')
            ->once()
            ->with(Mockery::type(DomainDateTime::class))
            ->andReturn([$first, $second]);
        $repository->shouldReceive('update')
            ->twice()
            ->with(Mockery::on(fn (VideoFile $video): bool => $video->originalPath() === null));

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('delete')->once()->with('/videos/originals/a.mkv');
        $storage->shouldReceive('delete')->once()->with('/videos/originals/b.mkv');

        $deleted = (new CleanupFailedOriginals($repository, $storage, 3))();

        $this->assertSame(2, $deleted);
    }

    public function test_skips_failed_without_original_path(): void
    {
        $withPath = $this->failedVideo('/videos/originals/a.mkv');
        $withoutPath = $this->failedVideo(null);

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findFailedOlderThan')->once()->andReturn([$withPath, $withoutPath]);
        $repository->shouldReceive('update')->once();

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('delete')->once()->with('/videos/originals/a.mkv');

        $deleted = (new CleanupFailedOriginals($repository, $storage, 3))();

        $this->assertSame(1, $deleted);
    }

    public function test_returns_zero_when_no_failed_video_files(): void
    {
        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findFailedOlderThan')->once()->andReturn([]);

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldNotReceive('delete');

        $deleted = (new CleanupFailedOriginals($repository, $storage, 3))();

        $this->assertSame(0, $deleted);
    }
}
