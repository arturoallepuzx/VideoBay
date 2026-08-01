<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Application\ServeVideoRange\ServeVideoRange;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\InvalidVideoRangeException;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Exception\VideoFileNotReadyException;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\ValueObject\ByteRange;
use App\Streaming\Domain\ValueObject\ByteRangeSpecification;
use App\Streaming\Domain\ValueObject\VideoLocation;
use Mockery;
use Tests\TestCase;

class ServeVideoRangeUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_streams_the_whole_video_when_no_range_is_requested(): void
    {
        $video = $this->video('ready', '/videos/movie.mp4');

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($video);

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('readChunks')
            ->once()
            ->with(
                Mockery::on(fn (VideoLocation $location): bool => $location->key() === '/videos/movie.mp4'),
                null,
            )
            ->andReturn(['first', 'second']);

        $result = (new ServeVideoRange($repository, $storage))($video->id()->value(), null);

        $this->assertNull($result->byteRange);
        $this->assertSame(4096, $result->totalSizeBytes);
        $this->assertSame('video/mp4', $result->mimeType);
        $this->assertSame(['first', 'second'], iterator_to_array($this->iterate($result->chunks)));
    }

    public function test_resolves_the_requested_specification_against_the_stored_size(): void
    {
        $video = $this->video('ready', '/videos/movie.mp4');

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($video);

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('readChunks')
            ->once()
            ->with(
                Mockery::type(VideoLocation::class),
                Mockery::on(fn (ByteRange $range): bool => ByteRange::create(100, 4095)->equals($range)),
            )
            ->andReturn(['chunk']);

        $result = (new ServeVideoRange($repository, $storage))(
            $video->id()->value(),
            ByteRangeSpecification::create(100, null),
        );

        $this->assertNotNull($result->byteRange);
        $this->assertTrue(ByteRange::create(100, 4095)->equals($result->byteRange));
    }

    public function test_rejects_a_specification_beyond_the_stored_size(): void
    {
        $video = $this->video('ready', '/videos/movie.mp4');

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($video);

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldNotReceive('readChunks');

        $this->expectException(InvalidVideoRangeException::class);

        (new ServeVideoRange($repository, $storage))(
            $video->id()->value(),
            ByteRangeSpecification::create(4096, null),
        );
    }

    public function test_throws_not_found_when_repository_returns_null(): void
    {
        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn(null);

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldNotReceive('readChunks');

        $this->expectException(VideoFileNotFoundException::class);

        (new ServeVideoRange($repository, $storage))(Uuid::generate()->value(), null);
    }

    public function test_throws_not_found_when_video_is_soft_deleted(): void
    {
        $video = $this->video('ready', '/videos/movie.mp4', deleted: true);

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($video);

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldNotReceive('readChunks');

        $this->expectException(VideoFileNotFoundException::class);

        (new ServeVideoRange($repository, $storage))($video->id()->value(), null);
    }

    public function test_throws_not_ready_when_video_is_pending(): void
    {
        $video = $this->video('pending', null);

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($video);

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldNotReceive('readChunks');

        $this->expectException(VideoFileNotReadyException::class);

        (new ServeVideoRange($repository, $storage))($video->id()->value(), null);
    }

    public function test_throws_not_ready_when_a_ready_video_has_no_stored_file(): void
    {
        $video = $this->video('ready', null);

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($video);

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldNotReceive('readChunks');

        $this->expectException(VideoFileNotReadyException::class);

        (new ServeVideoRange($repository, $storage))($video->id()->value(), null);
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
            'local',
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

    /**
     * @param  iterable<string>  $chunks
     * @return \Generator<string>
     */
    private function iterate(iterable $chunks): \Generator
    {
        yield from $chunks;
    }
}
