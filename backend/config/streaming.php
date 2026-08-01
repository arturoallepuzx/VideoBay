<?php

return [
    'videos_path' => env('VIDEO_STORAGE_PATH', '/var/lib/videobay/videos'),
    'originals_subdir' => 'originals',
    'ingestion_disk' => env('STREAMING_INGESTION_DISK', 'local'),
    'keep_original_after_processing' => (bool) env('STREAMING_KEEP_ORIGINAL_AFTER_PROCESSING', false),
    'cleanup_failed_after_days' => (int) env('STREAMING_CLEANUP_FAILED_AFTER_DAYS', 3),
    'ffmpeg' => [
        'binary' => env('FFMPEG_BINARY', '/usr/bin/ffmpeg'),
        'threads' => (int) env('FFMPEG_THREADS', 2),
        'preset' => env('FFMPEG_PRESET', 'slow'),
        'crf' => (int) env('FFMPEG_CRF', 18),
        'probe_timeout_seconds' => (int) env('FFPROBE_TIMEOUT_SECONDS', 60),
    ],
    'r2' => [
        'account_id' => env('R2_ACCOUNT_ID'),
        'access_key_id' => env('R2_ACCESS_KEY_ID'),
        'secret_access_key' => env('R2_SECRET_ACCESS_KEY'),
        'bucket' => env('R2_BUCKET'),
        'videos_prefix' => env('R2_VIDEOS_PREFIX', 'videos'),
        'copy_part_size' => (int) env('R2_COPY_PART_SIZE', 134217728),
        'copy_multipart_threshold' => (int) env('R2_COPY_MULTIPART_THRESHOLD', 134217728),
        'copy_concurrency' => (int) env('R2_COPY_CONCURRENCY', 15),
        'probe_url_ttl_seconds' => (int) env('R2_PROBE_URL_TTL_SECONDS', 120),
        'storage_price_per_gb_month' => env('R2_STORAGE_PRICE_PER_GB_MONTH'),
        'storage_free_gigabytes' => (float) env('R2_STORAGE_FREE_GIGABYTES', 10),
    ],
];
