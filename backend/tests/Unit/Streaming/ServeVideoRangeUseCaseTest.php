<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Application\ServeVideoRange\ServeVideoRange;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Exception\VideoFileNotReadyException;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use Mockery;
use Tests\TestCase;

class ServeVideoRangeUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function video(string $status, ?string $processedPath, bool $deleted = false): VideoFile
    {
        $now = new \DateTimeImmutable;

        return VideoFile::fromPersistence(
            Uuid::generate()->value(),
            Uuid::generate()->value(),
            null,
            null,
            $status === 'pending' ? '/videos/originals/x.mkv' : null,
            $processedPath,
            $processedPath !== null ? 120 : null,
            $processedPath !== null ? 4096 : null,
            null,
            $status,
            null,
            $now,
            $now,
            $deleted ? $now : null,
        );
    }

    public function test_returns_response_with_path_and_mime_for_ready_video(): void
    {
        $video = $this->video('ready', '/videos/movie.mp4');

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($video);

        $result = (new ServeVideoRange($repository))($video->id()->value());

        $this->assertSame('/videos/movie.mp4', $result->absolutePath);
        $this->assertSame('video/mp4', $result->mimeType);
    }

    public function test_throws_not_found_when_repository_returns_null(): void
    {
        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn(null);

        $this->expectException(VideoFileNotFoundException::class);

        (new ServeVideoRange($repository))(Uuid::generate()->value());
    }

    public function test_throws_not_found_when_video_is_soft_deleted(): void
    {
        $video = $this->video('ready', '/videos/movie.mp4', deleted: true);

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($video);

        $this->expectException(VideoFileNotFoundException::class);

        (new ServeVideoRange($repository))($video->id()->value());
    }

    public function test_throws_not_ready_when_video_is_pending(): void
    {
        $video = $this->video('pending', null);

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($video);

        $this->expectException(VideoFileNotReadyException::class);

        (new ServeVideoRange($repository))($video->id()->value());
    }
}
