<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\VideoLocation;
use PHPUnit\Framework\TestCase;

class VideoLocationTest extends TestCase
{
    public function test_create_exposes_disk_and_key(): void
    {
        $location = VideoLocation::create(StorageDisk::r2(), 'videos/abc.mp4');

        $this->assertSame('r2', $location->disk()->value());
        $this->assertSame('videos/abc.mp4', $location->key());
    }

    public function test_create_trims_surrounding_whitespace_from_key(): void
    {
        $location = VideoLocation::create(StorageDisk::local(), '  /var/lib/videobay/videos/abc.mp4  ');

        $this->assertSame('/var/lib/videobay/videos/abc.mp4', $location->key());
    }

    public function test_create_rejects_empty_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VideoLocation::create(StorageDisk::r2(), '');
    }

    public function test_create_rejects_whitespace_only_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VideoLocation::create(StorageDisk::r2(), '   ');
    }

    public function test_equals_is_true_for_same_disk_and_key(): void
    {
        $a = VideoLocation::create(StorageDisk::r2(), 'videos/abc.mp4');
        $b = VideoLocation::create(StorageDisk::r2(), 'videos/abc.mp4');

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_is_false_for_different_disk(): void
    {
        $a = VideoLocation::create(StorageDisk::r2(), 'videos/abc.mp4');
        $b = VideoLocation::create(StorageDisk::local(), 'videos/abc.mp4');

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_is_false_for_different_key(): void
    {
        $a = VideoLocation::create(StorageDisk::r2(), 'videos/abc.mp4');
        $b = VideoLocation::create(StorageDisk::r2(), 'videos/xyz.mp4');

        $this->assertFalse($a->equals($b));
    }
}
