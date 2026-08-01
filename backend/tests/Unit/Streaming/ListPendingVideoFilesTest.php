<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Application\ListPendingVideoFiles\ListPendingVideoFiles;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoTranscodeStorageInterface;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\StoredObject;
use Mockery;
use Tests\TestCase;

class ListPendingVideoFilesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_lists_root_and_original_pending_video_files(): void
    {
        $rootModifiedAt = new \DateTimeImmutable('2026-04-25 10:00:00');
        $originalModifiedAt = new \DateTimeImmutable('2026-04-25 11:00:00');

        $storage = Mockery::mock(VideoFileStorageInterface::class);
        $storage->shouldReceive('listVideos')
            ->once()
            ->andReturn([
                StoredObject::create('ready-to-register.mp4', 1024, $rootModifiedAt, StorageDisk::local()),
            ]);

        $transcodeStorage = Mockery::mock(VideoTranscodeStorageInterface::class);
        $transcodeStorage->shouldReceive('listOriginals')
            ->once()
            ->andReturn([
                StoredObject::create('source.mkv', 2048, $originalModifiedAt, StorageDisk::local()),
            ]);

        $response = (new ListPendingVideoFiles($storage, $transcodeStorage, StorageDisk::r2()))();

        $this->assertSame([
            'root' => [
                [
                    'filename' => 'ready-to-register.mp4',
                    'size_bytes' => 1024,
                    'modified_at' => $rootModifiedAt->format(\DateTimeInterface::ATOM),
                ],
            ],
            'originals' => [
                [
                    'filename' => 'source.mkv',
                    'size_bytes' => 2048,
                    'modified_at' => $originalModifiedAt->format(\DateTimeInterface::ATOM),
                ],
            ],
            'ingestion_disk' => 'r2',
        ], $response->toArray());
    }
}
