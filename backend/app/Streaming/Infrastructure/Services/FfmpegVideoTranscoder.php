<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\Exception\InvalidVideoFormatException;
use App\Streaming\Domain\Exception\TranscodingFailedException;
use App\Streaming\Domain\Interfaces\VideoTranscoderInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class FfmpegVideoTranscoder implements VideoTranscoderInterface
{
    public function __construct(
        private string $ffmpegBinary,
        private int $threads,
        private string $preset,
        private int $crf,
    ) {}

    public function probe(string $absolutePath): array
    {
        $ffprobe = dirname($this->ffmpegBinary).'/ffprobe';

        $process = new Process([
            $ffprobe,
            '-v', 'error',
            '-show_entries', 'format=duration,size:stream=codec_type,tags',
            '-of', 'json',
            $absolutePath,
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            if (! file_exists($ffprobe)) {
                throw new \RuntimeException(
                    sprintf('ffprobe binary not found at "%s". Check FFMPEG_BINARY config.', $ffprobe)
                );
            }

            throw InvalidVideoFormatException::forFile($absolutePath, trim($process->getErrorOutput()));
        }

        $data = json_decode($process->getOutput(), true);

        $durationSeconds = (int) round((float) ($data['format']['duration'] ?? 0));
        $fileSizeBytes = (int) ($data['format']['size'] ?? 0);

        $audioLanguage = null;
        foreach ($data['streams'] ?? [] as $stream) {
            if (($stream['codec_type'] ?? '') === 'audio') {
                $audioLanguage = $stream['tags']['language'] ?? null;
                break;
            }
        }

        return [
            'duration_seconds' => $durationSeconds,
            'file_size_bytes' => $fileSizeBytes,
            'audio_language' => $audioLanguage,
        ];
    }

    public function transcodeToMp4(string $inputAbsolutePath, string $outputAbsolutePath): void
    {
        $process = new Process([
            $this->ffmpegBinary,
            '-i', $inputAbsolutePath,
            '-map', '0:v:0',
            '-map', '0:a:0?',
            '-c:v', 'libx264',
            '-preset', $this->preset,
            '-crf', (string) $this->crf,
            '-c:a', 'aac',
            '-threads', (string) $this->threads,
            '-movflags', '+faststart',
            '-y',
            $outputAbsolutePath,
        ]);

        $process->setTimeout(null);

        try {
            $process->mustRun();
        } catch (ProcessFailedException) {
            throw TranscodingFailedException::ffmpegError(
                $inputAbsolutePath,
                trim($process->getErrorOutput()),
            );
        }
    }
}
