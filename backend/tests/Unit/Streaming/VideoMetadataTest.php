<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Domain\ValueObject\VideoMetadata;
use Tests\TestCase;

class VideoMetadataTest extends TestCase
{
    public function test_exposes_the_values_it_was_created_with(): void
    {
        $metadata = VideoMetadata::create(7413, 2105638987, 'spa');

        $this->assertSame(7413, $metadata->durationSeconds());
        $this->assertSame(2105638987, $metadata->fileSizeBytes());
        $this->assertSame('spa', $metadata->audioLanguage());
    }

    public function test_treats_a_blank_audio_language_as_absent(): void
    {
        $this->assertNull(VideoMetadata::create(120, 4096, '   ')->audioLanguage());
        $this->assertNull(VideoMetadata::create(120, 4096, null)->audioLanguage());
    }

    public function test_trims_the_audio_language(): void
    {
        $this->assertSame('eng', VideoMetadata::create(120, 4096, " eng\n")->audioLanguage());
    }

    public function test_rejects_a_negative_duration(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VideoMetadata::create(-1, 4096, null);
    }

    public function test_rejects_a_negative_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VideoMetadata::create(120, -1, null);
    }
}
