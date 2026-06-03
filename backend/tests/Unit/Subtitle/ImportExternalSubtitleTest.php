<?php

declare(strict_types=1);

namespace Tests\Unit\Subtitle;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Application\ImportExternalSubtitle\ImportExternalSubtitle;
use App\Subtitle\Domain\Entity\Subtitle;
use App\Subtitle\Domain\Interfaces\ExternalSubtitleProviderInterface;
use App\Subtitle\Domain\Interfaces\SubtitleFileStorageInterface;
use App\Subtitle\Domain\Interfaces\SubtitleMovieResolverInterface;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;
use App\Subtitle\Domain\ValueObject\ExternalSubtitleCandidate;
use App\Subtitle\Domain\ValueObject\ExternalSubtitleDownload;
use App\Subtitle\Domain\ValueObject\SubtitleFormat;
use App\Subtitle\Domain\ValueObject\SubtitleMovieMetadata;
use App\Subtitle\Infrastructure\Services\PlainTextSubtitleConverter;
use Mockery;
use Tests\TestCase;

class ImportExternalSubtitleTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_imports_external_subtitle_lazily(): void
    {
        $movieId = Uuid::generate();
        $userId = Uuid::generate();
        $movieResolver = Mockery::mock(SubtitleMovieResolverInterface::class);
        $movieResolver->shouldReceive('resolve')
            ->once()
            ->andReturn(SubtitleMovieMetadata::create($movieId, 'The Matrix', 603, 'tt0133093', 1999));

        $provider = Mockery::mock(ExternalSubtitleProviderInterface::class);
        $provider->shouldReceive('download')
            ->once()
            ->with(Mockery::type(ExternalSubtitleCandidate::class))
            ->andReturn(ExternalSubtitleDownload::create(
                "1\n00:00:01,000 --> 00:00:02,000\nHola\n",
                SubtitleFormat::srt(),
            ));

        $repository = Mockery::mock(SubtitleRepositoryInterface::class);
        $repository->shouldReceive('findByProviderAndExternalId')
            ->once()
            ->with('opensubtitles', '123')
            ->andReturn(null);
        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (Subtitle $subtitle): bool => $subtitle->source()->isExternal()
                && $subtitle->movieId()->equals($movieId)
                && $subtitle->uploadedByUserId()?->equals($userId) === true
                && $subtitle->language()->value() === 'es'
                && $subtitle->filePath() !== ''
            ));

        $storage = new class implements SubtitleFileStorageInterface
        {
            public string $storedContents = '';

            public ?string $deletedPath = null;

            public function store(Uuid $subtitleId, string $contents): string
            {
                $this->storedContents = $contents;

                return $subtitleId->value().'.vtt';
            }

            public function delete(string $filePath): void
            {
                $this->deletedPath = $filePath;
            }

            public function absolutePath(string $filePath): string
            {
                return '/tmp/'.$filePath;
            }

            public function exists(string $filePath): bool
            {
                return true;
            }
        };

        $useCase = new ImportExternalSubtitle(
            $repository,
            $movieResolver,
            $provider,
            new PlainTextSubtitleConverter,
            $storage,
            $this->transactionRunner(),
        );

        $response = $useCase($movieId->value(), $userId->value(), 'opensubtitles', '123', '456', 'es', 'Español');

        $this->assertSame('external', $response->source);
        $this->assertStringStartsWith('WEBVTT', $storage->storedContents);
    }

    public function test_deletes_stored_file_when_persistence_fails(): void
    {
        $movieId = Uuid::generate();
        $userId = Uuid::generate();
        $movieResolver = Mockery::mock(SubtitleMovieResolverInterface::class);
        $movieResolver->shouldReceive('resolve')
            ->once()
            ->andReturn(SubtitleMovieMetadata::create($movieId, 'The Matrix', 603, 'tt0133093', 1999));

        $provider = Mockery::mock(ExternalSubtitleProviderInterface::class);
        $provider->shouldReceive('download')
            ->once()
            ->andReturn(ExternalSubtitleDownload::create(
                "1\n00:00:01,000 --> 00:00:02,000\nHola\n",
                SubtitleFormat::srt(),
            ));

        $repository = Mockery::mock(SubtitleRepositoryInterface::class);
        $repository->shouldReceive('findByProviderAndExternalId')->once()->andReturn(null);
        $repository->shouldReceive('create')->once()->andThrow(new \RuntimeException('db failed'));

        $storage = new class implements SubtitleFileStorageInterface
        {
            public ?string $storedPath = null;

            public ?string $deletedPath = null;

            public function store(Uuid $subtitleId, string $contents): string
            {
                $this->storedPath = $subtitleId->value().'.vtt';

                return $this->storedPath;
            }

            public function delete(string $filePath): void
            {
                $this->deletedPath = $filePath;
            }

            public function absolutePath(string $filePath): string
            {
                return '/tmp/'.$filePath;
            }

            public function exists(string $filePath): bool
            {
                return true;
            }
        };

        $useCase = new ImportExternalSubtitle(
            $repository,
            $movieResolver,
            $provider,
            new PlainTextSubtitleConverter,
            $storage,
            $this->transactionRunner(),
        );

        $this->expectException(\RuntimeException::class);

        try {
            $useCase($movieId->value(), $userId->value(), 'opensubtitles', '123', '456', 'es', 'EspaÃ±ol');
        } finally {
            $this->assertSame($storage->storedPath, $storage->deletedPath);
        }
    }

    private function transactionRunner(): TransactionRunnerInterface
    {
        return new class implements TransactionRunnerInterface
        {
            public function run(callable $callback, int $attempts = 3): mixed
            {
                return $callback();
            }
        };
    }
}
