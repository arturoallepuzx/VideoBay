<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Infrastructure\Services\RoutingVideoFileStorage;
use Mockery;
use Tests\TestCase;

class RoutingVideoFileStorageTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_routes_location_operations_by_disk(): void
    {
        $local = Mockery::mock(VideoFileStorageInterface::class);
        $r2 = Mockery::mock(VideoFileStorageInterface::class);

        $localLocation = VideoLocation::create(StorageDisk::local(), '/videos/a.mp4');
        $r2Location = VideoLocation::create(StorageDisk::r2(), 'b.mp4');

        $local->shouldReceive('exists')->once()->with($localLocation)->andReturnTrue();
        $local->shouldReceive('delete')->once()->with($localLocation);
        $r2->shouldReceive('exists')->once()->with($r2Location)->andReturnTrue();
        $r2->shouldReceive('delete')->once()->with($r2Location);

        $router = new RoutingVideoFileStorage($local, $r2, StorageDisk::local());

        $this->assertTrue($router->exists($localLocation));
        $this->assertTrue($router->exists($r2Location));
        $router->delete($localLocation);
        $router->delete($r2Location);
    }

    public function test_routes_new_video_operations_to_ingestion_disk(): void
    {
        $local = Mockery::mock(VideoFileStorageInterface::class);
        $r2 = Mockery::mock(VideoFileStorageInterface::class);

        $r2->shouldReceive('locationForVideo')
            ->once()
            ->with('a.mp4')
            ->andReturn(VideoLocation::create(StorageDisk::r2(), 'a.mp4'));
        $r2->shouldReceive('listVideos')->once()->andReturn([]);
        $local->shouldNotReceive('locationForVideo');
        $local->shouldNotReceive('listVideos');

        $router = new RoutingVideoFileStorage($local, $r2, StorageDisk::r2());

        $this->assertTrue($router->locationForVideo('a.mp4')->disk()->isR2());
        $this->assertSame([], $router->listVideos());
    }

    public function test_renames_within_the_same_disk(): void
    {
        $local = Mockery::mock(VideoFileStorageInterface::class);
        $r2 = Mockery::mock(VideoFileStorageInterface::class);

        $from = VideoLocation::create(StorageDisk::r2(), 'from.mp4');
        $to = VideoLocation::create(StorageDisk::r2(), 'to.mp4');

        $renamed = [];
        $r2->shouldReceive('rename')
            ->once()
            ->andReturnUsing(function (VideoLocation $source, VideoLocation $target) use (&$renamed): void {
                $renamed[] = [$source, $target];
            });
        $local->shouldNotReceive('rename');

        $router = new RoutingVideoFileStorage($local, $r2, StorageDisk::local());

        $router->rename($from, $to);

        $this->assertCount(1, $renamed);
        $this->assertSame([$from, $to], $renamed[0]);
    }

    public function test_rejects_rename_across_disks(): void
    {
        $local = Mockery::mock(VideoFileStorageInterface::class);
        $r2 = Mockery::mock(VideoFileStorageInterface::class);

        $from = VideoLocation::create(StorageDisk::local(), '/videos/from.mp4');
        $to = VideoLocation::create(StorageDisk::r2(), 'to.mp4');

        $router = new RoutingVideoFileStorage($local, $r2, StorageDisk::local());

        $this->expectException(\InvalidArgumentException::class);

        $router->rename($from, $to);
    }
}
