<?php

declare(strict_types=1);

namespace Tests\Feature\Wishlist;

use Database\Factories\MovieFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    private function actingAsRealUser(): string
    {
        $userId = (string) UserFactory::new()->create()->uuid;
        $this->actingAsContext($userId);

        return $userId;
    }

    public function test_add_to_wishlist_returns_201_with_was_new_true(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->postJson('/api/wishlist/'.$movie->uuid)
            ->assertStatus(201)
            ->assertJson(['movie_id' => $movie->uuid, 'was_new' => true]);
    }

    public function test_adding_same_movie_twice_is_idempotent(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();

        $this->postJson('/api/wishlist/'.$movie->uuid)->assertStatus(201)->assertJson(['was_new' => true]);
        $this->postJson('/api/wishlist/'.$movie->uuid)->assertStatus(201)->assertJson(['was_new' => false]);
    }

    public function test_lists_wishlist_movies(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();
        $this->postJson('/api/wishlist/'.$movie->uuid)->assertStatus(201);

        $this->getJson('/api/wishlist')
            ->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.movie_id', $movie->uuid);
    }

    public function test_removes_from_wishlist(): void
    {
        $this->actingAsRealUser();
        $movie = MovieFactory::new()->create();
        $this->postJson('/api/wishlist/'.$movie->uuid)->assertStatus(201);

        $this->deleteJson('/api/wishlist/'.$movie->uuid)->assertSuccessful();

        $this->getJson('/api/wishlist')->assertStatus(200)->assertJsonPath('total', 0);
    }

    public function test_requires_authentication(): void
    {
        $this->withoutCsrfMiddleware();
        $movie = MovieFactory::new()->create();

        $this->postJson('/api/wishlist/'.$movie->uuid)->assertStatus(401);
    }
}
