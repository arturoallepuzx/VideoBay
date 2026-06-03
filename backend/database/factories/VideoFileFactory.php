<?php

namespace Database\Factories;

use App\Streaming\Infrastructure\Persistence\Models\EloquentVideoFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentVideoFile>
 */
class VideoFileFactory extends Factory
{
    protected $model = EloquentVideoFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'movie_id' => MovieFactory::new(),
            'original_filename' => null,
            'original_format' => null,
            'original_path' => null,
            'processed_path' => '/var/lib/videobay/videos/'.Str::uuid().'.mp4',
            'mime_type' => 'video/mp4',
            'duration_seconds' => 120,
            'file_size_bytes' => 4096,
            'audio_language' => null,
            'processing_status' => 'ready',
            'processing_error' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'processing_status' => 'pending',
            'original_filename' => 'source.mkv',
            'original_format' => 'mkv',
            'original_path' => '/var/lib/videobay/videos/originals/'.Str::uuid().'.mkv',
            'processed_path' => null,
            'duration_seconds' => null,
            'file_size_bytes' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'processing_status' => 'failed',
            'original_filename' => 'source.mkv',
            'original_format' => 'mkv',
            'original_path' => '/var/lib/videobay/videos/originals/'.Str::uuid().'.mkv',
            'processed_path' => null,
            'duration_seconds' => null,
            'file_size_bytes' => null,
            'processing_error' => 'ffmpeg failed',
        ]);
    }
}
