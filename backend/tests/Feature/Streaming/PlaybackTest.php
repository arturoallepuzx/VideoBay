<?php

declare(strict_types=1);

namespace Tests\Feature\Streaming;

use Database\Factories\MovieFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class PlaybackTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    private function actingAsRealUser(): void
    {
        $this->actingAsContext((string) UserFactory::new()->create()->uuid);
    }

    public function test_records_and_returns_progress(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->putJson('/api/playback/'.$movie->uuid, [
            'position_seconds' => 120,
            'duration_seconds' => 3600,
            'completed' => false,
        ])->assertStatus(204);

        $this->getJson('/api/playback/'.$movie->uuid)
            ->assertStatus(200)
            ->assertJson([
                'position_seconds' => 120,
                'duration_seconds' => 3600,
                'completed' => false,
            ]);
    }

    public function test_get_returns_defaults_when_no_progress(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->getJson('/api/playback/'.$movie->uuid)
            ->assertStatus(200)
            ->assertJsonPath('position_seconds', null)
            ->assertJsonPath('completed', false);
    }

    public function test_continue_watching_lists_in_progress_movies(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->putJson('/api/playback/'.$movie->uuid, [
            'position_seconds' => 50,
            'duration_seconds' => 3600,
            'completed' => false,
        ])->assertStatus(204);

        $this->getJson('/api/playback/continue-watching')
            ->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.movie_id', $movie->uuid);
    }

    public function test_history_lists_completed_movies_and_excludes_them_from_continue_watching(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->putJson('/api/playback/'.$movie->uuid, [
            'position_seconds' => 3600,
            'duration_seconds' => 3600,
            'completed' => true,
        ])->assertStatus(204);

        $this->getJson('/api/playback/history')
            ->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.movie_id', $movie->uuid);

        $this->getJson('/api/playback/continue-watching')
            ->assertStatus(200)
            ->assertJsonPath('total', 0);
    }

    public function test_remove_deletes_progress_and_drops_it_from_continue_watching(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->putJson('/api/playback/'.$movie->uuid, [
            'position_seconds' => 50,
            'duration_seconds' => 3600,
            'completed' => false,
        ])->assertStatus(204);

        $this->deleteJson('/api/playback/'.$movie->uuid)->assertStatus(204);

        $this->getJson('/api/playback/continue-watching')
            ->assertStatus(200)
            ->assertJsonPath('total', 0);

        $this->getJson('/api/playback/'.$movie->uuid)
            ->assertStatus(200)
            ->assertJsonPath('position_seconds', null);
    }

    public function test_remove_requires_authentication(): void
    {
        $this->withoutCsrfMiddleware();
        $movie = MovieFactory::new()->create();

        $this->deleteJson('/api/playback/'.$movie->uuid)->assertStatus(401);
    }

    public function test_record_requires_authentication(): void
    {
        $this->withoutCsrfMiddleware();
        $movie = MovieFactory::new()->create();

        $this->putJson('/api/playback/'.$movie->uuid, [
            'position_seconds' => 10,
            'completed' => false,
        ])->assertStatus(401);
    }

    public function test_record_validates_required_fields(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->putJson('/api/playback/'.$movie->uuid, [
            'completed' => false,
        ])->assertStatus(422);
    }
}
