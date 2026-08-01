<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Domain\Interfaces\VideoMetadataProbeInterface;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;
use App\Streaming\Infrastructure\Services\RoutingVideoMetadataProbe;
use Mockery;
use Tests\TestCase;

class RoutingVideoMetadataProbeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_probes_local_videos_with_the_local_adapter(): void
    {
        $location = VideoLocation::create(StorageDisk::local(), '/videos/movie.mp4');
        $expected = VideoMetadata::create(120, 4096, null);

        $local = Mockery::mock(VideoMetadataProbeInterface::class);
        $local->shouldReceive('probe')->once()->with($location)->andReturn($expected);

        $r2 = Mockery::mock(VideoMetadataProbeInterface::class);
        $r2->shouldNotReceive('probe');

        $this->assertSame($expected, (new RoutingVideoMetadataProbe($local, $r2))->probe($location));
    }

    public function test_probes_r2_videos_with_the_r2_adapter(): void
    {
        $location = VideoLocation::create(StorageDisk::r2(), 'videos/movie.mp4');
        $expected = VideoMetadata::create(120, 4096, null);

        $local = Mockery::mock(VideoMetadataProbeInterface::class);
        $local->shouldNotReceive('probe');

        $r2 = Mockery::mock(VideoMetadataProbeInterface::class);
        $r2->shouldReceive('probe')->once()->with($location)->andReturn($expected);

        $this->assertSame($expected, (new RoutingVideoMetadataProbe($local, $r2))->probe($location));
    }
}
