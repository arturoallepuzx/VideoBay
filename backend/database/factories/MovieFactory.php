<?php

namespace Database\Factories;

use App\Catalog\Infrastructure\Persistence\Models\EloquentMovie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentMovie>
 */
class MovieFactory extends Factory
{
    protected $model = EloquentMovie::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tmdb_id' => fake()->unique()->numberBetween(1, 2_000_000),
            'imdb_id' => null,
            'title' => fake()->sentence(3),
            'original_title' => fake()->sentence(3),
            'overview' => fake()->paragraph(),
            'release_date' => fake()->date(),
            'runtime_minutes' => fake()->numberBetween(60, 180),
            'original_language' => 'en',
            'poster_path' => null,
            'backdrop_path' => null,
            'tmdb_rating' => fake()->randomFloat(1, 0, 10),
            'cached_at' => now(),
        ];
    }
}
