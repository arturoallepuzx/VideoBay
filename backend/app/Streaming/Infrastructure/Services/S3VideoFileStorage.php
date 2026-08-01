<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\Exception\VideoFileStorageException;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\ValueObject\ByteRange;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\StoredObject;
use App\Streaming\Domain\ValueObject\VideoLocation;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\S3\S3Client;
use Psr\Http\Message\StreamInterface;

class S3VideoFileStorage implements VideoFileStorageInterface
{
    private const CHUNK_SIZE_BYTES = 1048576;

    private string $videosPrefix;

    public function __construct(
        private S3Client $client,
        private string $bucket,
        string $videosPrefix,
        private int $copyPartSize,
        private int $copyMultipartThreshold,
        private int $copyConcurrency,
    ) {
        $normalizedPrefix = trim($videosPrefix, '/');
        $this->videosPrefix = $normalizedPrefix !== '' ? $normalizedPrefix.'/' : '';
    }

    public function locationForVideo(string $name): VideoLocation
    {
        return VideoLocation::create(StorageDisk::r2(), $this->videosPrefix.$name);
    }

    public function exists(VideoLocation $location): bool
    {
        try {
            return $this->client->doesObjectExistV2($this->bucket, $location->key());
        } catch (AwsException $e) {
            throw VideoFileStorageException::readFailed($location->key(), $e);
        }
    }

    public function delete(VideoLocation $location): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $location->key(),
            ]);
        } catch (AwsException $e) {
            throw VideoFileStorageException::deleteFailed($location->key(), $e);
        }
    }

    public function rename(VideoLocation $from, VideoLocation $to): void
    {
        try {
            $this->client->copy(
                $this->bucket,
                $from->key(),
                $this->bucket,
                $to->key(),
                'private',
                [
                    'part_size' => $this->copyPartSize,
                    'mup_threshold' => $this->copyMultipartThreshold,
                    'concurrency' => $this->copyConcurrency,
                ],
            );
        } catch (MultipartUploadException $e) {
            $this->abortMultipartUploadIfPossible($e);

            throw VideoFileStorageException::copyFailed($from->key(), $to->key(), $e);
        } catch (AwsException $e) {
            throw VideoFileStorageException::copyFailed($from->key(), $to->key(), $e);
        }

        if (! $this->exists($to)) {
            throw VideoFileStorageException::copyFailed($from->key(), $to->key());
        }

        $this->delete($from);
    }

    /** @return list<StoredObject> */
    public function listVideos(): array
    {
        $parameters = ['Bucket' => $this->bucket];
        if ($this->videosPrefix !== '') {
            $parameters['Prefix'] = $this->videosPrefix;
        }

        $objects = [];

        try {
            foreach ($this->client->getPaginator('ListObjectsV2', $parameters) as $page) {
                foreach ($page['Contents'] ?? [] as $object) {
                    $name = $this->stripPrefix((string) $object['Key']);
                    if ($name === '') {
                        continue;
                    }

                    $objects[] = StoredObject::create(
                        $name,
                        (int) $object['Size'],
                        \DateTimeImmutable::createFromInterface($object['LastModified']),
                        StorageDisk::r2(),
                    );
                }
            }
        } catch (AwsException $e) {
            throw VideoFileStorageException::listFailed($this->videosPrefix, $e);
        }

        usort($objects, fn (StoredObject $a, StoredObject $b): int => $b->modifiedAt() <=> $a->modifiedAt());

        return $objects;
    }

    public function readChunks(VideoLocation $location, ?ByteRange $range): iterable
    {
        $parameters = [
            'Bucket' => $this->bucket,
            'Key' => $location->key(),
            '@http' => ['stream' => true],
        ];

        if ($range !== null) {
            $parameters['Range'] = sprintf('bytes=%d-%d', $range->start(), $range->end());
        }

        try {
            $body = $this->client->getObject($parameters)['Body'];
        } catch (AwsException $e) {
            throw VideoFileStorageException::readFailed($location->key(), $e);
        }

        return $this->readChunksFrom($body, $range?->length());
    }

    /** @return \Generator<string> */
    private function readChunksFrom(StreamInterface $body, ?int $remainingBytes): \Generator
    {
        try {
            while (! $body->eof() && ($remainingBytes === null || $remainingBytes > 0)) {
                $length = $remainingBytes === null
                    ? self::CHUNK_SIZE_BYTES
                    : min(self::CHUNK_SIZE_BYTES, $remainingBytes);

                $chunk = $body->read($length);

                if ($chunk === '') {
                    break;
                }

                if ($remainingBytes !== null) {
                    $remainingBytes -= strlen($chunk);
                }

                yield $chunk;
            }
        } finally {
            $body->close();
        }
    }

    private function stripPrefix(string $key): string
    {
        if ($this->videosPrefix !== '' && str_starts_with($key, $this->videosPrefix)) {
            return substr($key, strlen($this->videosPrefix));
        }

        return $key;
    }

    private function abortMultipartUploadIfPossible(MultipartUploadException $exception): void
    {
        $state = $exception->getState();

        if (! $state->isInitiated()) {
            return;
        }

        try {
            $this->client->abortMultipartUpload($state->getId());
        } catch (AwsException) {
            return;
        }
    }
}
