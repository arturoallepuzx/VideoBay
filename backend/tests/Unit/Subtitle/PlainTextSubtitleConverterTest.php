<?php

declare(strict_types=1);

namespace Tests\Unit\Subtitle;

use App\Subtitle\Domain\ValueObject\SubtitleFormat;
use App\Subtitle\Infrastructure\Services\PlainTextSubtitleConverter;
use Tests\TestCase;

class PlainTextSubtitleConverterTest extends TestCase
{
    public function test_converts_srt_to_sanitized_webvtt(): void
    {
        $srt = <<<'SRT'
1
00:00:01,000 --> 00:00:02,500
<b>Hello</b> world

2
00:00:03,000 --> 00:00:04,000
Second cue
SRT;

        $vtt = (new PlainTextSubtitleConverter)->toWebVtt($srt, SubtitleFormat::srt());

        $this->assertStringStartsWith("WEBVTT\n\n", $vtt);
        $this->assertStringContainsString('00:00:01.000 --> 00:00:02.500', $vtt);
        $this->assertStringContainsString('Hello world', $vtt);
        $this->assertStringNotContainsString('<b>', $vtt);
    }

    public function test_adds_webvtt_header_to_vtt_without_header(): void
    {
        $contents = "00:00:01.000 --> 00:00:02.000\nHola\n";

        $vtt = (new PlainTextSubtitleConverter)->toWebVtt($contents, SubtitleFormat::vtt());

        $this->assertStringStartsWith("WEBVTT\n\n", $vtt);
    }
}
