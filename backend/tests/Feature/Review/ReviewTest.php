<?php

declare(strict_types=1);

namespace Tests\Feature\Review;

use Database\Factories\MovieFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    private function actingAsRealUser(): string
    {
        $userId = (string) UserFactory::new()->create()->uuid;
        $this->actingAsContext($userId);

        return $userId;
    }

    public function test_create_review_returns_201_with_fields(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->postJson('/api/movies/'.$movie->uuid.'/reviews', [
            'rating' => 8,
            'body' => 'Great film',
            'contains_spoilers' => false,
        ])->assertStatus(201)->assertJson([
            'movie_id' => $movie->uuid,
            'rating' => 8,
            'body' => 'Great film',
            'contains_spoilers' => false,
            'likes_count' => 0,
        ]);
    }

    public function test_create_review_validates_rating(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->postJson('/api/movies/'.$movie->uuid.'/reviews', ['body' => 'no rating'])->assertStatus(422);
        $this->postJson('/api/movies/'.$movie->uuid.'/reviews', ['rating' => 11])->assertStatus(422);
        $this->postJson('/api/movies/'.$movie->uuid.'/reviews', ['rating' => 0])->assertStatus(422);
    }

    public function test_lists_movie_reviews_with_author(): void
    {
        $userId = $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();
        $this->postJson('/api/movies/'.$movie->uuid.'/reviews', ['rating' => 9])->assertStatus(201);

        $this->getJson('/api/movies/'.$movie->uuid.'/reviews')
            ->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.rating', 9)
            ->assertJsonPath('items.0.author.uuid', $userId);
    }

    public function test_requires_authentication(): void
    {
        $this->withoutCsrfMiddleware();
        $movie = MovieFactory::new()->create();

        $this->postJson('/api/movies/'.$movie->uuid.'/reviews', ['rating' => 5])->assertStatus(401);
    }
}
