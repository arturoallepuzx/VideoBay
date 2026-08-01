<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Application\FailVideoFileRegistration\FailVideoFileRegistration;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;
use Mockery;
use Tests\TestCase;

class FailVideoFileRegistrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_records_the_reason_on_a_processing_video(): void
    {
        $videoFile = $this->processingVideo();

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($videoFile);
        $repository->shouldReceive('update')->once();

        (new FailVideoFileRegistration($repository, $this->transactionRunner()))(
            $videoFile->id()->value(),
            'Failed to copy "a" to "b".',
        );

        $this->assertTrue($videoFile->processingStatus()->isFailed());
        $this->assertSame('Failed to copy "a" to "b".', $videoFile->processingError());
    }

    public function test_leaves_a_video_that_already_finished_untouched(): void
    {
        $videoFile = $this->processingVideo();
        $videoFile->markReady(
            VideoLocation::create(StorageDisk::r2(), 'videos/lista.mp4'),
            VideoMetadata::create(120, 4096, null),
        );

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($videoFile);
        $repository->shouldNotReceive('update');

        (new FailVideoFileRegistration($repository, $this->transactionRunner()))($videoFile->id()->value(), 'tarde');

        $this->assertTrue($videoFile->isReady());
        $this->assertNull($videoFile->processingError());
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
