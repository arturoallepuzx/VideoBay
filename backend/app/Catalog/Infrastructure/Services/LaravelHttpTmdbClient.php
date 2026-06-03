<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Services;

use App\Catalog\Domain\Exception\TmdbServiceUnavailableException;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class LaravelHttpTmdbClient implements TmdbClientInterface
{
    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private HttpFactory $http,
        private string $readAccessToken,
        private string $baseUrl,
        private string $language,
    ) {
        if (trim($readAccessToken) === '') {
            throw new \InvalidArgumentException('TMDB_READ_ACCESS_TOKEN is required.');
        }

        if (trim($baseUrl) === '') {
            throw new \InvalidArgumentException('TMDB_BASE_URL is required.');
        }
    }

    public function searchMovies(string $query, int $page = 1): array
    {
        return $this->getPaginated('/search/movie', [
            'query' => $query,
            'page' => $page,
        ]);
    }

    public function getMovieDetail(int $tmdbId): array
    {
        return $this->get('/movie/'.$tmdbId, [
            'append_to_response' => 'credits,external_ids',
        ]);
    }

    public function getMovieRecommendations(int $tmdbId, int $page = 1): array
    {
        return $this->getPaginated('/movie/'.$tmdbId.'/recommendations', [
            'page' => $page,
        ]);
    }

    public function searchPeople(string $query, int $page = 1): array
    {
        return $this->getPaginated('/search/person', [
            'query' => $query,
            'page' => $page,
        ]);
    }

    public function getPersonDetail(int $tmdbId): array
    {
        return $this->get('/person/'.$tmdbId, []);
    }

    public function getPersonMovieCredits(int $tmdbId): array
    {
        $data = $this->get('/person/'.$tmdbId.'/movie_credits', []);

        return [
            'cast' => is_array($data['cast'] ?? null) ? array_values($data['cast']) : [],
            'crew' => is_array($data['crew'] ?? null) ? array_values($data['crew']) : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query): array
    {
        return $this->request($path, $query)->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{results: list<array<string, mixed>>, page: int, total_pages: int, total_results: int}
     */
    private function getPaginated(string $path, array $query): array
    {
        $data = $this->request($path, $query)->json() ?? [];

        return [
            'results' => is_array($data['results'] ?? null) ? array_values($data['results']) : [],
            'page' => (int) ($data['page'] ?? 1),
            'total_pages' => (int) ($data['total_pages'] ?? 0),
            'total_results' => (int) ($data['total_results'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function request(string $path, array $query): Response
    {
        try {
            $response = $this->client()->get($this->baseUrl.$path, array_merge(
                ['language' => $this->language],
                $query,
            ));
        } catch (ConnectionException $e) {
            throw TmdbServiceUnavailableException::unreachable($e);
        }

        if (! $response->successful()) {
            throw TmdbServiceUnavailableException::unexpectedResponse(
                $response->status(),
                $response->body(),
            );
        }

        return $response;
    }

    private function client(): PendingRequest
    {
        return $this->http
            ->withToken($this->readAccessToken)
            ->acceptJson()
            ->timeout(self::TIMEOUT_SECONDS);
    }
}
