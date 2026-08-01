<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\Exception\InvalidVideoFormatException;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;
use Symfony\Component\Process\Process;

class FfprobeMetadataReader
{
    public function __construct(
        private string $ffmpegBinary,
        private int $timeoutSeconds,
    ) {}

    public function read(VideoLocation $location, string $source): VideoMetadata
    {
        $ffprobe = dirname($this->ffmpegBinary).'/ffprobe';

        $process = new Process([
            $ffprobe,
            '-v', 'error',
            '-show_entries', 'format=duration,size:stream=codec_type,tags',
            '-of', 'json',
            $source,
        ]);

        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            if (! is_file($ffprobe)) {
                throw new \RuntimeException(
                    sprintf('ffprobe binary not found at "%s". Check FFMPEG_BINARY config.', $ffprobe)
                );
            }

            throw InvalidVideoFormatException::forLocation($location);
        }

        $data = json_decode($process->getOutput(), true);

        if (! is_array($data)) {
            throw InvalidVideoFormatException::forLocation($location);
        }

        return VideoMetadata::create(
            (int) round((float) ($data['format']['duration'] ?? 0)),
            (int) ($data['format']['size'] ?? 0),
            $this->firstAudioLanguage($data['streams'] ?? []),
        );
    }

    /** @param array<int, mixed> $streams */
    private function firstAudioLanguage(array $streams): ?string
    {
        foreach ($streams as $stream) {
            if (! is_array($stream) || ($stream['codec_type'] ?? '') !== 'audio') {
                continue;
            }

            $language = $stream['tags']['language'] ?? null;

            return is_string($language) ? $language : null;
        }

        return null;
    }
}
