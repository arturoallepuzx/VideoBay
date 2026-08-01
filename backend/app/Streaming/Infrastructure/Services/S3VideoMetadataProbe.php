<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\Exception\VideoFileStorageException;
use App\Streaming\Domain\Interfaces\VideoMetadataProbeInterface;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class S3VideoMetadataProbe implements VideoMetadataProbeInterface
{
    public function __construct(
        private S3Client $client,
        private string $bucket,
        private int $urlTtlSeconds,
        private FfprobeMetadataReader $reader,
    ) {}

    public function probe(VideoLocation $location): VideoMetadata
    {
        return $this->reader->read($location, $this->temporaryReadUrl($location));
    }

    private function temporaryReadUrl(VideoLocation $location): string
    {
        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $location->key(),
        ]);

        try {
            $request = $this->client->createPresignedRequest($command, sprintf('+%d seconds', $this->urlTtlSeconds));
        } catch (AwsException $e) {
            throw VideoFileStorageException::readFailed($location->key(), $e);
        }

        return (string) $request->getUri();
    }
}
