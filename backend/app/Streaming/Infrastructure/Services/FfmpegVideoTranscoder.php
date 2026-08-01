<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

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
