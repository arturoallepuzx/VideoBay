<?php

return [
    'videos_path' => env('VIDEO_STORAGE_PATH', '/var/lib/videobay/videos'),
    'originals_subdir' => 'originals',
    'keep_original_after_processing' => (bool) env('STREAMING_KEEP_ORIGINAL_AFTER_PROCESSING', false),
    'cleanup_failed_after_days' => (int) env('STREAMING_CLEANUP_FAILED_AFTER_DAYS', 3),
    'ffmpeg' => [
        'binary' => env('FFMPEG_BINARY', '/usr/bin/ffmpeg'),
        'threads' => (int) env('FFMPEG_THREADS', 2),
        'preset' => env('FFMPEG_PRESET', 'slow'),
        'crf' => (int) env('FFMPEG_CRF', 18),
    ],
];
