<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Console;

use App\Streaming\Application\CleanupFailedOriginals\CleanupFailedOriginals;
use Illuminate\Console\Command;

class CleanupFailedOriginalsCommand extends Command
{
    protected $signature = 'streaming:cleanup-failed-originals';

    protected $description = 'Delete original files of video_files in failed state older than the configured threshold.';

    public function __construct(private CleanupFailedOriginals $cleanupFailedOriginals)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $deleted = ($this->cleanupFailedOriginals)();

        $this->info(sprintf('Deleted %d original file(s) of failed video_files.', $deleted));

        return self::SUCCESS;
    }
}
