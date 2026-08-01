<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\Exception\VideoFileStorageException;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoTranscodeStorageInterface;
use App\Streaming\Domain\ValueObject\ByteRange;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\StoredObject;
use App\Streaming\Domain\ValueObject\VideoLocation;

class LocalVideoFileStorage implements VideoFileStorageInterface, VideoTranscodeStorageInterface
{
    private const CHUNK_SIZE_BYTES = 1048576;

    private string $videosPath;

    private string $originalsPath;

    public function __construct(string $videosPath, string $originalsSubdir)
    {
        $this->videosPath = rtrim($videosPath, '/');
        $this->originalsPath = $this->videosPath.'/'.trim($originalsSubdir, '/');

        $this->ensureDirectoryExists($this->videosPath);
        $this->ensureDirectoryExists($this->originalsPath);
    }

    public function exists(VideoLocation $location): bool
    {
        return is_file($location->key());
    }

    public function delete(VideoLocation $location): void
    {
        $path = $location->key();

        if (file_exists($path) && ! unlink($path)) {
            throw VideoFileStorageException::deleteFailed($path);
        }
    }

    public function rename(VideoLocation $from, VideoLocation $to): void
    {
        if (! rename($from->key(), $to->key())) {
            throw VideoFileStorageException::renameFailed($from->key(), $to->key());
        }
    }

    public function locationForVideo(string $name): VideoLocation
    {
        $this->assertPlainFileName($name);

        return VideoLocation::create(StorageDisk::local(), $this->videosPath.'/'.$name);
    }

    public function listVideos(): array
    {
        return $this->listObjectsIn($this->videosPath);
    }

    public function readChunks(VideoLocation $location, ?ByteRange $range): iterable
    {
        $handle = @fopen($location->key(), 'rb');

        if ($handle === false) {
            throw VideoFileStorageException::readFailed($location->key());
        }

        if ($range !== null && fseek($handle, $range->start()) !== 0) {
            fclose($handle);

            throw VideoFileStorageException::readFailed($location->key());
        }

        return $this->readChunksFrom($handle, $range?->length());
    }

    public function locationForOriginal(string $name): VideoLocation
    {
        $this->assertPlainFileName($name);

        return VideoLocation::create(StorageDisk::local(), $this->originalsPath.'/'.$name);
    }

    public function listOriginals(): array
    {
        return $this->listObjectsIn($this->originalsPath);
    }

    public function store(string $temporaryPath, VideoLocation $destination): void
    {
        $destinationPath = $destination->key();

        if (@rename($temporaryPath, $destinationPath)) {
            return;
        }

        if (! @copy($temporaryPath, $destinationPath)) {
            throw VideoFileStorageException::moveFailed($temporaryPath, $destinationPath);
        }

        @unlink($temporaryPath);
    }

    /**
     * @param  resource  $handle
     * @return \Generator<string>
     */
    private function readChunksFrom($handle, ?int $remainingBytes): \Generator
    {
        try {
            while (! feof($handle) && ($remainingBytes === null || $remainingBytes > 0)) {
                $length = $remainingBytes === null
                    ? self::CHUNK_SIZE_BYTES
                    : min(self::CHUNK_SIZE_BYTES, $remainingBytes);

                $chunk = fread($handle, $length);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                if ($remainingBytes !== null) {
                    $remainingBytes -= strlen($chunk);
                }

                yield $chunk;
            }
        } finally {
            fclose($handle);
        }
    }

    private function assertPlainFileName(string $name): void
    {
        if (basename($name) !== $name || $name === '.' || $name === '..') {
            throw new \InvalidArgumentException(sprintf('Invalid video file name "%s".', $name));
        }
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path) && ! @mkdir($path, 0775, true) && ! is_dir($path)) {
            throw VideoFileStorageException::directoryCreationFailed($path);
        }
    }

    /** @return list<StoredObject> */
    private function listObjectsIn(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $objects = [];
        foreach (new \DirectoryIterator($directory) as $file) {
            if ($file->isDot() || $file->isDir() || ! $file->isFile()) {
                continue;
            }

            $objects[] = StoredObject::create(
                $file->getFilename(),
                (int) $file->getSize(),
                new \DateTimeImmutable('@'.$file->getMTime()),
                StorageDisk::local(),
            );
        }

        usort($objects, fn (StoredObject $a, StoredObject $b): int => $b->modifiedAt() <=> $a->modifiedAt());

        return $objects;
    }
}
