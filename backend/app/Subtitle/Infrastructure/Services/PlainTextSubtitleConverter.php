<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Services;

use App\Subtitle\Domain\Interfaces\SubtitleConverterInterface;
use App\Subtitle\Domain\ValueObject\SubtitleFormat;

class PlainTextSubtitleConverter implements SubtitleConverterInterface
{
    public function toWebVtt(string $contents, SubtitleFormat $format): string
    {
        $normalized = $this->normalize($contents);

        if ($format->isSrt()) {
            return $this->srtToVtt($normalized);
        }

        return $this->sanitizeVtt($normalized);
    }

    private function srtToVtt(string $contents): string
    {
        $lines = explode("\n", $contents);
        $output = ['WEBVTT', ''];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed !== '' && preg_match('/^\d+$/', $trimmed) === 1) {
                continue;
            }

            if (str_contains($line, '-->')) {
                $output[] = str_replace(',', '.', $line);

                continue;
            }

            $output[] = $this->sanitizeCueText($line);
        }

        return rtrim(implode("\n", $output))."\n";
    }

    private function sanitizeVtt(string $contents): string
    {
        $lines = explode("\n", $contents);
        $output = [];

        if (! str_starts_with(ltrim($contents), 'WEBVTT')) {
            $output[] = 'WEBVTT';
            $output[] = '';
        }

        foreach ($lines as $line) {
            if (str_contains($line, '-->') || str_starts_with($line, 'WEBVTT') || trim($line) === '') {
                $output[] = $line;

                continue;
            }

            $output[] = $this->sanitizeCueText($line);
        }

        return rtrim(implode("\n", $output))."\n";
    }

    private function normalize(string $contents): string
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);
        $contents = str_replace("\0", '', $contents);

        return trim($contents)."\n";
    }

    private function sanitizeCueText(string $line): string
    {
        return trim(strip_tags($line));
    }
}
