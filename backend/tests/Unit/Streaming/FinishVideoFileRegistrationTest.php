<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Application\FinishVideoFileRegistration\FinishVideoFileRegistration;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\VideoFileStorageException;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoMetadataProbeInterface;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;
use Mockery;
use Tests\TestCase;

class FinishVideoFileRegistrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_moves_the_video_and_marks_it_ready(): void
    {
        $videoFile = $this->processingVideo();
        $uuid = $videoFile->id()->value();
        $target = VideoLocation::create(StorageDisk::r2(), 'videos/'.$uuid.'.mp4');

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('locationForVideo')->once()->with($uuid.'.mp4')->andReturn($target);
        $storage->shouldReceive('exists')->once()->andReturn(true);
        $storage->shouldReceive('rename')->once()->with($videoFile->originalLocation(), $target)->ordered();

        $metadataProbe = Mockery::mock(VideoMetadataProbeInterface::class);
        $metadataProbe->shouldReceive('probe')->once()->with($target)->andReturn(VideoMetadata::create(7413, 2105638987, 'spa'))->ordered();

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($videoFile);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($videoFile);
        $repository->shouldReceive('update')->once()->ordered();

        $eventDispatcher = Mockery::mock(DomainEventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')->once();

        (new FinishVideoFileRegistration($repository, $storage, $metadataProbe, $this->transactionRunner(), $eventDispatcher))($uuid);

        $this->assertTrue($videoFile->isReady());
        $this->assertSame('videos/'.$uuid.'.mp4', $videoFile->processedLocation()?->key());
        $this->assertSame(7413, $videoFile->metadata()?->durationSeconds());
        $this->assertNull($videoFile->originalLocation());
    }

    public function test_lets_a_storage_failure_bubble_up_so_the_queue_can_retry(): void
    {
        $videoFile = $this->processingVideo();
        $uuid = $videoFile->id()->value();
        $target = VideoLocation::create(StorageDisk::r2(), 'videos/'.$uuid.'.mp4');

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('locationForVideo')->once()->andReturn($target);
        $storage->shouldReceive('exists')->once()->andReturn(true);
        $storage->shouldReceive('rename')->once()->andThrow(VideoFileStorageException::copyFailed('a', 'b'));

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($videoFile);
        $repository->shouldNotReceive('update');

        $eventDispatcher = Mockery::mock(DomainEventDispatcherInterface::class);
        $eventDispatcher->shouldNotReceive('dispatch');

        $this->expectException(VideoFileStorageException::class);

        try {
            (new FinishVideoFileRegistration(
                $repository,
                $storage,
                Mockery::mock(VideoMetadataProbeInterface::class),
                $this->transactionRunner(),
                $eventDispatcher,
            ))($uuid);
        } finally {
            $this->assertTrue($videoFile->processingStatus()->isProcessing());
        }
    }

    public function test_does_nothing_when_the_video_is_no_longer_processing(): void
    {
        $videoFile = $this->processingVideo();
        $videoFile->markReady(
            VideoLocation::create(StorageDisk::r2(), 'videos/ya-esta.mp4'),
            VideoMetadata::create(120, 4096, null),
        );

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldNotReceive('rename');

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($videoFile);
        $repository->shouldNotReceive('update');

        $eventDispatcher = Mockery::mock(DomainEventDispatcherInterface::class);
        $eventDispatcher->shouldNotReceive('dispatch');

        (new FinishVideoFileRegistration(
            $repository,
            $storage,
            Mockery::mock(VideoMetadataProbeInterface::class),
            $this->transactionRunner(),
            $eventDispatcher,
        ))($videoFile->id()->value());

        $this->assertTrue($videoFile->isReady());
    }

    private function processingVideo(): VideoFile
    {
        return VideoFile::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            null,
            null,
            VideoLocation::create(StorageDisk::r2(), 'videos/pelicula.mp4'),
            null,
            null,
            VideoFileProcessingStatus::processing(),
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
}
