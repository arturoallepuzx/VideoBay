<?php

declare(strict_types=1);

namespace App\Catalog\Application\ResolveBarcode;

use App\Catalog\Domain\Exception\TmdbServiceUnavailableException;
use App\Catalog\Domain\Interfaces\BarcodeApiClientInterface;
use App\Catalog\Domain\Interfaces\BarcodeLookupRepositoryInterface;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use App\Catalog\Domain\Service\ProductTitleNormalizer;
use App\Shared\Domain\ValueObject\BarcodeValue;

class ResolveBarcode
{
    public function __construct(
        private BarcodeLookupRepositoryInterface $barcodeLookupRepository,
        private MovieRepositoryInterface $movieRepository,
        private BarcodeApiClientInterface $barcodeApiClient,
        private TmdbClientInterface $tmdbClient,
        private ProductTitleNormalizer $productTitleNormalizer,
    ) {}

    public function __invoke(string $barcodeRaw): ResolveBarcodeResponse
    {
        $barcode = BarcodeValue::create($barcodeRaw);

        $cached = $this->barcodeLookupRepository->findByBarcode($barcode);
        if ($cached !== null && $cached->movieId() !== null) {
            $movie = $this->movieRepository->findByUuid($cached->movieId());
            if ($movie !== null) {
                return ResolveBarcodeResponse::fromLocalHit($movie);
            }
        }

        $externalTitle = $this->barcodeApiClient->resolveTitle($barcode);
        if ($externalTitle === null) {
            return ResolveBarcodeResponse::unresolved($barcode);
        }

        $searchTitle = $this->productTitleNormalizer->normalize($externalTitle);

        try {
            $tmdbResult = $this->tmdbClient->searchMovies($searchTitle, 1);

            if ($tmdbResult['results'] === [] && $searchTitle !== $externalTitle) {
                $tmdbResult = $this->tmdbClient->searchMovies($externalTitle, 1);
            }
        } catch (TmdbServiceUnavailableException) {
            return ResolveBarcodeResponse::externalTitleOnly($barcode, $externalTitle);
        }

        return ResolveBarcodeResponse::fromTmdbCandidates($barcode, $externalTitle, $tmdbResult);
    }
}
