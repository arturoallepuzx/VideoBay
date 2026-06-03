<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\PlaybackProgress;
use PHPUnit\Framework\TestCase;

class PlaybackProgressTest extends TestCase
{
    private function make(): PlaybackProgress
    {
        return PlaybackProgress::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            500,
            8820,
            false,
        );
    }

    public function test_ddd_create_clamps_negative_position_to_zero(): void
    {
        $progress = PlaybackProgress::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            -10,
            null,
            false,
        );

        $this->assertSame(0, $progress->positionSeconds());
    }

    public function test_report_position_updates_when_changed(): void
    {
        $progress = $this->make();

        $progress->reportPosition(1200, 8820, false);

        $this->assertSame(1200, $progress->positionSeconds());
        $this->assertTrue($progress->wasModified());
    }

    public function test_report_position_noop_when_identical(): void
    {
        $progress = $this->make();

        $progress->reportPosition(500, 8820, false);

        $this->assertFalse($progress->wasModified());
    }

    public function test_report_position_marks_completed(): void
    {
        $progress = $this->make();

        $progress->reportPosition(8820, 8820, true);

        $this->assertTrue($progress->completed());
        $this->assertTrue($progress->wasModified());
    }

    public function test_report_position_clamps_negative(): void
    {
        $progress = $this->make();

        $progress->reportPosition(-5, 8820, false);

        $this->assertSame(0, $progress->positionSeconds());
    }
}
