<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Services;

use App\Subtitle\Domain\Exception\ExternalSubtitleUnavailableException;
use App\Subtitle\Domain\Interfaces\ExternalSubtitleProviderInterface;
use App\Subtitle\Domain\ValueObject\ExternalSubtitleCandidate;
use App\Subtitle\Domain\ValueObject\ExternalSubtitleDownload;
use App\Subtitle\Domain\ValueObject\SubtitleFormat;
use App\Subtitle\Domain\ValueObject\SubtitleLabel;
use App\Subtitle\Domain\ValueObject\SubtitleLanguage;
use App\Subtitle\Domain\ValueObject\SubtitleMovieMetadata;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

class OpenSubtitlesProvider implements ExternalSubtitleProviderInterface
{
    private const PROVIDER = 'opensubtitles';

    private const TIMEOUT_SECONDS = 15;

    private ?string $token = null;

    public function __construct(
        private HttpFactory $http,
        private string $apiKey,
        private string $baseUrl,
        private string $userAgent,
        private ?string $username,
        private ?string $password,
    ) {}

    public function search(SubtitleMovieMetadata $movie, ?SubtitleLanguage $language): array
    {
        $query = [];

        if ($movie->imdbIdWithoutPrefix() !== null && $movie->imdbIdWithoutPrefix() !== '') {
            $query['imdb_id'] = $movie->imdbIdWithoutPrefix();
        } elseif ($movie->tmdbId() !== null) {
            $query['tmdb_id'] = $movie->tmdbId();
        } else {
            $query['query'] = $movie->title();
            if ($movie->releaseYear() !== null) {
                $query['year'] = $movie->releaseYear();
            }
        }

        if ($language !== null) {
            $query['languages'] = $language->value();
        }

        $data = $this->get('/subtitles', $query);
        $rows = is_array($data['data'] ?? null) ? array_values($data['data']) : [];
        $candidates = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $candidate = $this->candidateFromRow($row);
            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    public function download(ExternalSubtitleCandidate $candidate): ExternalSubtitleDownload
    {
        $data = $this->post('/download', ['file_id' => (int) $candidate->fileId()], true);
        $link = $data['link'] ?? null;

        if (! is_string($link) || trim($link) === '') {
            throw ExternalSubtitleUnavailableException::unexpectedResponse(200, 'Missing download link.');
        }

        try {
            $response = $this->http->timeout(self::TIMEOUT_SECONDS)->get($link);
        } catch (ConnectionException $e) {
            throw ExternalSubtitleUnavailableException::unavailable($e);
        }

        if (! $response->successful()) {
            throw ExternalSubtitleUnavailableException::unexpectedResponse($response->status(), $response->body());
        }

        $filename = is_string($data['file_name'] ?? null) ? (string) $data['file_name'] : '';
        $format = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        if ($format !== 'srt' && $format !== 'vtt') {
            $body = $response->body();
            $format = str_starts_with(ltrim($body), 'WEBVTT') ? 'vtt' : 'srt';
        }

        return ExternalSubtitleDownload::create($response->body(), SubtitleFormat::create($format));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query): array
    {
        try {
            $response = $this->client()->get($this->url($path), $query);
        } catch (ConnectionException $e) {
            throw ExternalSubtitleUnavailableException::unavailable($e);
        }

        if (! $response->successful()) {
            throw ExternalSubtitleUnavailableException::unexpectedResponse($response->status(), $response->body());
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload, bool $authenticated): array
    {
        try {
            $client = $this->client();

            if ($authenticated) {
                $client = $client->withToken($this->token());
            }

            $response = $client->post($this->url($path), $payload);
        } catch (ConnectionException $e) {
            throw ExternalSubtitleUnavailableException::unavailable($e);
        }

        if (! $response->successful()) {
            throw ExternalSubtitleUnavailableException::unexpectedResponse($response->status(), $response->body());
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    private function token(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        if (trim((string) $this->username) === '' || trim((string) $this->password) === '') {
            throw ExternalSubtitleUnavailableException::unexpectedResponse(401, 'OpenSubtitles credentials are required for downloads.');
        }

        $data = $this->post('/login', [
            'username' => $this->username,
            'password' => $this->password,
        ], false);

        $token = $data['token'] ?? null;

        if (! is_string($token) || trim($token) === '') {
            throw ExternalSubtitleUnavailableException::unexpectedResponse(200, 'Missing OpenSubtitles auth token.');
        }

        $this->token = $token;

        return $this->token;
    }

    private function client(): PendingRequest
    {
        if (trim($this->apiKey) === '') {
            throw ExternalSubtitleUnavailableException::unexpectedResponse(401, 'OpenSubtitles API key is required.');
        }

        return $this->http
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Api-Key' => $this->apiKey,
                'User-Agent' => trim($this->userAgent) !== '' ? $this->userAgent : 'VideoBay',
            ])
            ->timeout(self::TIMEOUT_SECONDS);
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/').$path;
    }

    private function candidateFromRow(array $row): ?ExternalSubtitleCandidate
    {
        $attributes = is_array($row['attributes'] ?? null) ? $row['attributes'] : [];
        $files = is_array($attributes['files'] ?? null) ? array_values($attributes['files']) : [];
        $firstFile = is_array($files[0] ?? null) ? $files[0] : null;

        if ($firstFile === null || ! isset($firstFile['file_id'])) {
            return null;
        }

        $language = is_string($attributes['language'] ?? null) ? (string) $attributes['language'] : '';
        $externalId = (string) ($attributes['subtitle_id'] ?? $row['id'] ?? '');
        $fileId = (string) $firstFile['file_id'];
        $release = is_string($attributes['release'] ?? null) ? trim((string) $attributes['release']) : '';
        $fileName = is_string($firstFile['file_name'] ?? null) ? trim((string) $firstFile['file_name']) : '';
        $label = $release !== '' ? $release : ($fileName !== '' ? $fileName : strtoupper($language));

        if ($language === '' || $externalId === '' || $fileId === '') {
            return null;
        }

        return ExternalSubtitleCandidate::create(
            self::PROVIDER,
            $externalId,
            $fileId,
            SubtitleLanguage::create($language),
            SubtitleLabel::create($label),
            isset($attributes['download_count']) ? (int) $attributes['download_count'] : null,
            (bool) ($attributes['hearing_impaired'] ?? false),
        );
    }
}
