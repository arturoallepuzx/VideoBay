<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Application\RegisterExistingVideoFile\RegisterExistingVideoFile;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\InvalidVideoFormatException;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Interfaces\MovieResolverForStreamingInterface;
use App\Streaming\Domain\Interfaces\VideoFileRegistrationJobDispatcherInterface;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoMetadataProbeInterface;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;
use Mockery;
use Tests\TestCase;

class RegisterExistingVideoFileTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_registers_as_processing_and_queues_the_move(): void
    {
        $movieId = Uuid::generate();
        $source = VideoLocation::create(StorageDisk::r2(), 'videos/pelicula.mp4');

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('locationForVideo')->once()->with('pelicula.mp4')->andReturn($source);
        $storage->shouldReceive('exists')->once()->with($source)->andReturn(true);
        $storage->shouldNotReceive('rename');

        $metadataProbe = Mockery::mock(VideoMetadataProbeInterface::class);
        $metadataProbe->shouldReceive('probe')->once()->with($source)->andReturn(VideoMetadata::create(7413, 2105638987, null));

        $created = null;
        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('create')->once()->andReturnUsing(function (VideoFile $videoFile) use (&$created): void {
            $created = $videoFile;
        });

        $dispatched = null;
        $jobDispatcher = Mockery::mock(VideoFileRegistrationJobDispatcherInterface::class);
        $jobDispatcher->shouldReceive('dispatch')->once()->andReturnUsing(function (Uuid $uuid) use (&$dispatched): void {
            $dispatched = $uuid;
        });

        $response = (new RegisterExistingVideoFile(
            $repository,
            $storage,
            $metadataProbe,
            $this->movieResolver($movieId),
            $this->transactionRunner(),
            $jobDispatcher,
        ))('pelicula.mp4', $movieId->value(), null);

        $this->assertSame('processing', $created?->processingStatus()->value());
        $this->assertSame('videos/pelicula.mp4', $created?->originalLocation()?->key());
        $this->assertNull($created?->processedLocation());
        $this->assertNull($created?->metadata());
        $this->assertTrue($dispatched?->equals($created->id()));
        $this->assertSame('processing', $response->processingStatus);
    }

    public function test_rejects_a_source_that_is_not_in_storage(): void
    {
        $source = VideoLocation::create(StorageDisk::r2(), 'videos/fantasma.mp4');

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('locationForVideo')->once()->andReturn($source);
        $storage->shouldReceive('exists')->once()->andReturn(false);

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldNotReceive('create');

        $jobDispatcher = Mockery::mock(VideoFileRegistrationJobDispatcherInterface::class);
        $jobDispatcher->shouldNotReceive('dispatch');

        $this->expectException(VideoFileNotFoundException::class);

        (new RegisterExistingVideoFile(
            $repository,
            $storage,
            Mockery::mock(VideoMetadataProbeInterface::class),
            $this->movieResolver(Uuid::generate()),
            $this->transactionRunner(),
            $jobDispatcher,
        ))('fantasma.mp4', Uuid::generate()->value(), null);
    }

    public function test_does_not_register_a_video_it_cannot_read(): void
    {
        $source = VideoLocation::create(StorageDisk::r2(), 'videos/rota.mp4');

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('locationForVideo')->once()->andReturn($source);
        $storage->shouldReceive('exists')->once()->andReturn(true);

        $metadataProbe = Mockery::mock(VideoMetadataProbeInterface::class);
        $metadataProbe->shouldReceive('probe')->once()->andThrow(InvalidVideoFormatException::forLocation($source));

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldNotReceive('create');

        $jobDispatcher = Mockery::mock(VideoFileRegistrationJobDispatcherInterface::class);
        $jobDispatcher->shouldNotReceive('dispatch');

        $this->expectException(InvalidVideoFormatException::class);

        (new RegisterExistingVideoFile(
            $repository,
            $storage,
            $metadataProbe,
            $this->movieResolver(Uuid::generate()),
            $this->transactionRunner(),
            $jobDispatcher,
        ))('rota.mp4', Uuid::generate()->value(), null);
    }

    private function movieResolver(Uuid $movieId): MovieResolverForStreamingInterface
    {
        $resolver = Mockery::mock(MovieResolverForStreamingInterface::class);
        $resolver->shouldReceive('resolveByUuid')->andReturn($movieId);
        $resolver->shouldReceive('resolveByTmdbId')->andReturn($movieId);

        return $resolver;
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
