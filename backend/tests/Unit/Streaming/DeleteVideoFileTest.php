<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Application\DeleteVideoFile\DeleteVideoFile;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\VideoFileStorageException;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use Mockery;
use Tests\TestCase;

class DeleteVideoFileTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_deletes_the_stored_file_before_marking_the_record_as_deleted(): void
    {
        $videoFile = $this->readyVideo();

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('delete')->once()->ordered();

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($videoFile);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($videoFile);
        $repository->shouldReceive('update')->once()->ordered();

        $useCase = new DeleteVideoFile($repository, $storage, $this->transactionRunner());

        $useCase($videoFile->id()->value());

        $this->assertTrue($videoFile->isDeleted());
    }

    public function test_keeps_the_record_when_the_stored_file_cannot_be_deleted(): void
    {
        $videoFile = $this->readyVideo();

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('delete')
            ->once()
            ->andThrow(VideoFileStorageException::deleteFailed('videos/ready.mp4'));

        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->andReturn($videoFile);
        $repository->shouldNotReceive('update');

        $useCase = new DeleteVideoFile($repository, $storage, $this->transactionRunner());

        $this->expectException(VideoFileStorageException::class);

        try {
            $useCase($videoFile->id()->value());
        } finally {
            $this->assertFalse($videoFile->isDeleted());
        }
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
            'videos/ready.mp4',
            'r2',
            120,
            4096,
            'es',
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
}
