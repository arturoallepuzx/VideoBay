<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Application\TranscodeVideo\TranscodeVideo;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\TranscodingFailedException;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoTranscoderInterface;
use Mockery;
use Tests\TestCase;

class TranscodeVideoTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function pendingVideo(string $originalPath): VideoFile
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
            'pending',
            null,
            $now,
            $now,
            null,
        );
    }

    private function readyVideo(): VideoFile
    {
        $now = new \DateTimeImmutable;

        return VideoFile::fromPersistence(
            Uuid::generate()->value(),
            Uuid::generate()->value(),
            null,
            null,
            null,
            '/videos/ready.mp4',
            120,
            4096,
            null,
            'ready',
            null,
            $now,
            $now,
            null,
        );
    }

    private function transactionRunner(): TransactionRunnerInterface
    {
        return new class implements TransactionRunnerInterface
        {
            public function run(callable $callback, int $attempts = 3): mixed
            {
                return $callback();
            }
        };
    }

    private function eventDispatcher(): DomainEventDispatcherInterface
    {
        return new class implements DomainEventDispatcherInterface
        {
            public array $events = [];

            public function dispatch(object $event): void
            {
                $this->events[] = $event;
            }
        };
    }

    public function test_transcodes_pending_video_and_deletes_original_when_not_keeping(): void
    {
        $video = $this->pendingVideo('/videos/originals/src.mkv');
        $uuid = $video->id()->value();
        $outputPath = '/videos/'.$uuid.'.mp4';

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($video);
        $repository->shouldReceive('update')->atLeast()->once();

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('absolutePathInRoot')->with($uuid.'.mp4')->andReturn($outputPath);
        $storage->shouldReceive('delete')->once()->with('/videos/originals/src.mkv');

        $transcoder = Mockery::mock(VideoTranscoderInterface::class);
        $transcoder->shouldReceive('transcodeToMp4')->once()->with('/videos/originals/src.mkv', $outputPath);
        $transcoder->shouldReceive('probe')->once()->with($outputPath)->andReturn([
            'duration_seconds' => 120,
            'file_size_bytes' => 5000,
            'audio_language' => 'eng',
        ]);

        (new TranscodeVideo($repository, $transcoder, $storage, $this->transactionRunner(), $this->eventDispatcher(), false))($uuid);

        $this->assertTrue($video->isReady());
        $this->assertNull($video->originalPath());
    }

    public function test_keeps_original_when_configured(): void
    {
        $video = $this->pendingVideo('/videos/originals/src.mkv');
        $uuid = $video->id()->value();
        $outputPath = '/videos/'.$uuid.'.mp4';

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($video);
        $repository->shouldReceive('update')->atLeast()->once();

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('absolutePathInRoot')->with($uuid.'.mp4')->andReturn($outputPath);
        $storage->shouldNotReceive('delete');

        $transcoder = Mockery::mock(VideoTranscoderInterface::class);
        $transcoder->shouldReceive('transcodeToMp4')->once();
        $transcoder->shouldReceive('probe')->once()->andReturn([
            'duration_seconds' => 120,
            'file_size_bytes' => 5000,
            'audio_language' => null,
        ]);

        (new TranscodeVideo($repository, $transcoder, $storage, $this->transactionRunner(), $this->eventDispatcher(), true))($uuid);

        $this->assertTrue($video->isReady());
        $this->assertSame('/videos/originals/src.mkv', $video->originalPath());
    }

    public function test_does_nothing_when_video_is_not_pending(): void
    {
        $video = $this->readyVideo();

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($video);
        $repository->shouldNotReceive('update');

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $transcoder = Mockery::mock(VideoTranscoderInterface::class);
        $transcoder->shouldNotReceive('transcodeToMp4');

        (new TranscodeVideo($repository, $transcoder, $storage, $this->transactionRunner(), $this->eventDispatcher(), false))($video->id()->value());

        $this->assertTrue($video->isReady());
    }

    public function test_marks_failed_when_transcoding_fails(): void
    {
        $video = $this->pendingVideo('/videos/originals/src.mkv');
        $uuid = $video->id()->value();
        $outputPath = '/videos/'.$uuid.'.mp4';

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($video);
        $repository->shouldReceive('update')->atLeast()->once();

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('absolutePathInRoot')->with($uuid.'.mp4')->andReturn($outputPath);
        $storage->shouldNotReceive('delete');

        $transcoder = Mockery::mock(VideoTranscoderInterface::class);
        $transcoder->shouldReceive('transcodeToMp4')
            ->once()
            ->andThrow(TranscodingFailedException::ffmpegError('/videos/originals/src.mkv', 'boom'));

        (new TranscodeVideo($repository, $transcoder, $storage, $this->transactionRunner(), $this->eventDispatcher(), false))($uuid);

        $this->assertSame('failed', $video->processingStatus()->value());
    }
}
