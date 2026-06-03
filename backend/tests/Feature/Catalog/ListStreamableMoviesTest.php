<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Database\Factories\MovieFactory;
use Database\Factories\VideoFileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class ListStreamableMoviesTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    public function test_lists_only_movies_with_a_ready_video(): void
    {
        $this->actingAsContext();
        $streamable = MovieFactory::new()->create();
        VideoFileFactory::new()->create(['movie_id' => $streamable->id]);
        MovieFactory::new()->create();

        $this->getJson('/api/catalog/movies/streamable')
            ->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.movie_id', $streamable->uuid);
    }

    public function test_excludes_movies_whose_video_is_not_ready(): void
    {
        $this->actingAsContext();
        $movie = MovieFactory::new()->create();
        VideoFileFactory::new()->pending()->create(['movie_id' => $movie->id]);

        $this->getJson('/api/catalog/movies/streamable')
            ->assertStatus(200)
            ->assertJsonPath('total', 0);
    }

    public function test_excludes_movies_whose_video_is_soft_deleted(): void
    {
        $this->actingAsContext();
        $movie = MovieFactory::new()->create();
        VideoFileFactory::new()->create(['movie_id' => $movie->id, 'deleted_at' => now()]);

        $this->getJson('/api/catalog/movies/streamable')
            ->assertStatus(200)
            ->assertJsonPath('total', 0);
    }

    public function test_requires_authentication(): void
    {
        MovieFactory::new()->create();

        $this->getJson('/api/catalog/movies/streamable')->assertStatus(401);
    }
}
