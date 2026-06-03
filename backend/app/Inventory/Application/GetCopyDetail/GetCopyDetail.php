<?php

declare(strict_types=1);

namespace App\Inventory\Application\GetCopyDetail;

use App\Inventory\Domain\Exception\PhysicalCopyNotFoundException;
use App\Inventory\Domain\Interfaces\MovieSummaryResolverInterface;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class GetCopyDetail
{
    public function __construct(
        private PhysicalCopyRepositoryInterface $physicalCopyRepository,
        private MovieSummaryResolverInterface $movieSummaryResolver,
    ) {}

    public function __invoke(string $copyUuid): GetCopyDetailResponse
    {
        $uuid = Uuid::create($copyUuid);
        $copy = $this->physicalCopyRepository->findByUuid($uuid);

        if ($copy === null) {
            throw PhysicalCopyNotFoundException::forUuid($uuid);
        }

        $movies = $this->movieSummaryResolver->resolveMany([$copy->movieId()]);
        $movie = $movies[$copy->movieId()->value()] ?? null;

        return GetCopyDetailResponse::create($copy, $movie);
    }
}
