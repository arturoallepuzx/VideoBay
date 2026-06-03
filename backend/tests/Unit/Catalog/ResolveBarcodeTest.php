<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Catalog\Application\ResolveBarcode\ResolveBarcode;
use App\Catalog\Domain\Exception\TmdbServiceUnavailableException;
use App\Catalog\Domain\Interfaces\BarcodeApiClientInterface;
use App\Catalog\Domain\Interfaces\BarcodeLookupRepositoryInterface;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use App\Catalog\Domain\Service\ProductTitleNormalizer;
use Mockery;
use Tests\TestCase;

class ResolveBarcodeTest extends TestCase
{
    private const BARCODE = '8412345678901';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function useCase(
        BarcodeLookupRepositoryInterface $lookup,
        MovieRepositoryInterface $movies,
        BarcodeApiClientInterface $barcodeApi,
        TmdbClientInterface $tmdb,
    ): ResolveBarcode {
        return new ResolveBarcode($lookup, $movies, $barcodeApi, $tmdb, new ProductTitleNormalizer);
    }

    public function test_returns_unresolved_when_no_cache_and_no_external_title(): void
    {
        $lookup = Mockery::mock(BarcodeLookupRepositoryInterface::class);
        $lookup->shouldReceive('findByBarcode')->once()->andReturn(null);

        $barcodeApi = Mockery::mock(BarcodeApiClientInterface::class);
        $barcodeApi->shouldReceive('resolveTitle')->once()->andReturn(null);

        $tmdb = Mockery::mock(TmdbClientInterface::class);
        $tmdb->shouldNotReceive('searchMovies');

        $movies = Mockery::mock(MovieRepositoryInterface::class);

        $response = $this->useCase($lookup, $movies, $barcodeApi, $tmdb)(self::BARCODE);

        $this->assertSame('unresolved', $response->source);
        $this->assertSame(self::BARCODE, $response->barcode);
    }

    public function test_returns_external_title_only_when_tmdb_unavailable(): void
    {
        $lookup = Mockery::mock(BarcodeLookupRepositoryInterface::class);
        $lookup->shouldReceive('findByBarcode')->once()->andReturn(null);

        $barcodeApi = Mockery::mock(BarcodeApiClientInterface::class);
        $barcodeApi->shouldReceive('resolveTitle')->once()->andReturn('The Matrix DVD');

        $tmdb = Mockery::mock(TmdbClientInterface::class);
        $tmdb->shouldReceive('searchMovies')
            ->once()
            ->andThrow(TmdbServiceUnavailableException::unreachable(new \Exception('down')));

        $movies = Mockery::mock(MovieRepositoryInterface::class);

        $response = $this->useCase($lookup, $movies, $barcodeApi, $tmdb)(self::BARCODE);

        $this->assertSame('external_title_only', $response->source);
        $this->assertSame('The Matrix DVD', $response->externalTitle);
    }

    public function test_returns_tmdb_candidates_when_external_title_resolves(): void
    {
        $lookup = Mockery::mock(BarcodeLookupRepositoryInterface::class);
        $lookup->shouldReceive('findByBarcode')->once()->andReturn(null);

        $barcodeApi = Mockery::mock(BarcodeApiClientInterface::class);
        $barcodeApi->shouldReceive('resolveTitle')->once()->andReturn('The Matrix');

        $tmdb = Mockery::mock(TmdbClientInterface::class);
        $tmdb->shouldReceive('searchMovies')->once()->andReturn([
            'results' => [
                [
                    'id' => 603,
                    'title' => 'The Matrix',
                    'release_date' => '1999-03-30',
                    'poster_path' => '/m.jpg',
                    'overview' => 'A hacker.',
                ],
            ],
            'page' => 1,
            'total_pages' => 1,
            'total_results' => 1,
        ]);

        $movies = Mockery::mock(MovieRepositoryInterface::class);

        $response = $this->useCase($lookup, $movies, $barcodeApi, $tmdb)(self::BARCODE);

        $this->assertSame('tmdb_candidates', $response->source);
        $this->assertSame('The Matrix', $response->externalTitle);
        $this->assertCount(1, $response->candidates);
        $this->assertSame(603, $response->candidates[0]['tmdb_id']);
    }
}
