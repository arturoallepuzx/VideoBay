<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Auth\Infrastructure\Http\Middleware\AuthenticateAccessToken;
use App\Auth\Infrastructure\Http\Middleware\EnsureXsrfCookie;
use App\Auth\Infrastructure\Http\Middleware\RequireRole;
use App\Auth\Infrastructure\Http\Middleware\VerifyCsrfTokenStateless;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use Mockery;
use Tests\TestCase;

class SearchMoviesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_movies_search_returns_results_from_tmdb(): void
    {
        $tmdbMock = Mockery::mock(TmdbClientInterface::class);
        $tmdbMock->shouldReceive('searchMovies')
            ->once()
            ->with('matrix', 1)
            ->andReturn([
                'results' => [
                    [
                        'id' => 603,
                        'title' => 'The Matrix',
                        'original_title' => 'The Matrix',
                        'overview' => 'A hacker discovers reality is a simulation.',
                        'release_date' => '1999-03-30',
                        'poster_path' => '/matrix.jpg',
                        'backdrop_path' => '/matrix-bg.jpg',
                        'vote_average' => 8.2,
                        'original_language' => 'en',
                    ],
                ],
                'page' => 1,
                'total_pages' => 1,
                'total_results' => 1,
            ]);

        $this->app->instance(TmdbClientInterface::class, $tmdbMock);

        $response = $this
            ->withoutMiddleware([
                AuthenticateAccessToken::class,
                RequireRole::class,
                EnsureXsrfCookie::class,
                VerifyCsrfTokenStateless::class,
            ])
            ->getJson('/api/catalog/movies/search?query=matrix&page=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => [
                    'tmdb_id',
                    'title',
                    'original_title',
                    'overview',
                    'release_date',
                    'poster_path',
                    'backdrop_path',
                    'tmdb_rating',
                    'original_language',
                ],
            ],
            'page',
            'total_pages',
            'total_results',
        ]);
        $response->assertJson([
            'page' => 1,
            'total_pages' => 1,
            'total_results' => 1,
            'results' => [
                [
                    'tmdb_id' => 603,
                    'title' => 'The Matrix',
                    'tmdb_rating' => 8.2,
                ],
            ],
        ]);
    }

    public function test_get_movies_search_rejects_empty_query(): void
    {
        $response = $this
            ->withoutMiddleware([
                AuthenticateAccessToken::class,
                RequireRole::class,
                EnsureXsrfCookie::class,
                VerifyCsrfTokenStateless::class,
            ])
            ->getJson('/api/catalog/movies/search?query=');

        $response->assertStatus(422);
    }
}
