<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Services;

use App\Catalog\Domain\Interfaces\BarcodeApiClientInterface;
use App\Shared\Domain\ValueObject\BarcodeValue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

class UpcDatabaseBarcodeApiClient implements BarcodeApiClientInterface
{
    private const TIMEOUT_SECONDS = 5;

    public function __construct(
        private HttpFactory $http,
        private string $apiKey,
        private string $baseUrl,
    ) {}

    public function resolveTitle(BarcodeValue $barcode): ?string
    {
        if (trim($this->apiKey) === '' || trim($this->baseUrl) === '') {
            return null;
        }

        try {
            $response = $this->http
                ->withToken($this->apiKey)
                ->acceptJson()
                ->timeout(self::TIMEOUT_SECONDS)
                ->get(rtrim($this->baseUrl, '/').'/product/'.$barcode->value());
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (! is_array($data)) {
            return null;
        }

        $title = $data['title'] ?? null;
        if (! is_string($title)) {
            return null;
        }

        $trimmed = trim($title);

        return $trimmed === '' ? null : $trimmed;
    }
}
