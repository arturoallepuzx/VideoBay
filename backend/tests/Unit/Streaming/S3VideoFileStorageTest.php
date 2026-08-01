<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Domain\Exception\VideoFileStorageException;
use App\Streaming\Domain\ValueObject\ByteRange;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Infrastructure\Services\S3VideoFileStorage;
use Aws\Exception\MultipartUploadException;
use Aws\Multipart\UploadState;
use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use Tests\TestCase;

class S3VideoFileStorageTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function storageWithPrefix(string $prefix): S3VideoFileStorage
    {
        return new S3VideoFileStorage(
            Mockery::mock(S3Client::class),
            'videobay',
            $prefix,
            134217728,
            134217728,
            15,
        );
    }

    public function test_reads_the_object_as_a_stream_instead_of_buffering_it(): void
    {
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('getObject')
            ->once()
            ->with(Mockery::on(fn (array $parameters): bool => ($parameters['@http'] ?? null) === ['stream' => true]))
            ->andReturn(new Result(['Body' => Utils::streamFor('data')]));

        $storage = new S3VideoFileStorage($client, 'videobay', 'videos', 134217728, 134217728, 15);
        $location = VideoLocation::create(StorageDisk::r2(), 'videos/movie.mp4');

        $this->assertSame('data', implode('', iterator_to_array($storage->readChunks($location, null))));
    }

    public function test_requests_only_the_bytes_of_the_given_range(): void
    {
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('getObject')
            ->once()
            ->with(Mockery::on(fn (array $parameters): bool => ($parameters['Range'] ?? null) === 'bytes=10-19'))
            ->andReturn(new Result(['Body' => Utils::streamFor('0123456789')]));

        $storage = new S3VideoFileStorage($client, 'videobay', 'videos', 134217728, 134217728, 15);
        $location = VideoLocation::create(StorageDisk::r2(), 'videos/movie.mp4');

        $chunks = $storage->readChunks($location, ByteRange::create(10, 19));

        $this->assertSame('0123456789', implode('', iterator_to_array($chunks)));
    }

    public function test_builds_video_keys_under_the_configured_prefix(): void
    {
        $location = $this->storageWithPrefix('videos')->locationForVideo('abc.mp4');

        $this->assertSame('videos/abc.mp4', $location->key());
        $this->assertTrue($location->disk()->isR2());
    }

    public function test_normalizes_surrounding_slashes_in_the_prefix(): void
    {
        $this->assertSame(
            'videos/abc.mp4',
            $this->storageWithPrefix('/videos/')->locationForVideo('abc.mp4')->key(),
        );
    }

    public function test_builds_video_keys_at_the_bucket_root_without_prefix(): void
    {
        $this->assertSame(
            'abc.mp4',
            $this->storageWithPrefix('')->locationForVideo('abc.mp4')->key(),
        );
    }

    public function test_copies_in_parallel_parts_from_the_configured_threshold(): void
    {
        $options = null;

        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('copy')
            ->once()
            ->andReturnUsing(function (...$arguments) use (&$options): void {
                $options = $arguments[5];
            });
        $client->shouldReceive('doesObjectExistV2')->once()->andReturn(true);
        $client->shouldReceive('deleteObject')->once();

        $storage = new S3VideoFileStorage($client, 'videobay', 'videos', 134217728, 134217728, 15);

        $storage->rename(
            VideoLocation::create(StorageDisk::r2(), 'videos/from.mp4'),
            VideoLocation::create(StorageDisk::r2(), 'videos/to.mp4'),
        );

        $this->assertSame([
            'part_size' => 134217728,
            'mup_threshold' => 134217728,
            'concurrency' => 15,
        ], $options);
    }

    public function test_discards_the_uploaded_parts_when_a_multipart_copy_fails(): void
    {
        $uploadId = ['Bucket' => 'videobay', 'Key' => 'videos/to.mp4', 'UploadId' => 'upload-1'];

        $state = Mockery::mock(UploadState::class);
        $state->shouldReceive('isInitiated')->once()->andReturn(true);
        $state->shouldReceive('getId')->once()->andReturn($uploadId);

        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('copy')->once()->andThrow(new MultipartUploadException($state));
        $client->shouldReceive('abortMultipartUpload')->once()->with($uploadId);

        $storage = new S3VideoFileStorage($client, 'videobay', 'videos', 134217728, 134217728, 15);

        $this->expectException(VideoFileStorageException::class);

        $storage->rename(
            VideoLocation::create(StorageDisk::r2(), 'videos/from.mp4'),
            VideoLocation::create(StorageDisk::r2(), 'videos/to.mp4'),
        );
    }

    public function test_does_not_delete_the_source_when_the_copy_fails(): void
    {
        $state = Mockery::mock(UploadState::class);
        $state->shouldReceive('isInitiated')->once()->andReturn(false);

        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('copy')->once()->andThrow(new MultipartUploadException($state));
        $client->shouldNotReceive('deleteObject');

        $storage = new S3VideoFileStorage($client, 'videobay', 'videos', 134217728, 134217728, 15);

        $this->expectException(VideoFileStorageException::class);

        $storage->rename(
            VideoLocation::create(StorageDisk::r2(), 'videos/from.mp4'),
            VideoLocation::create(StorageDisk::r2(), 'videos/to.mp4'),
        );
    }
}
