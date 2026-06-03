<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Services;

use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\Interfaces\MovieSummaryResolverInterface;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Order\Domain\ValueObject\CopyDetails;

class InventoryCopyDetailsProvider implements CopyDetailsProviderInterface
{
    public function __construct(
        private PhysicalCopyRepositoryInterface $physicalCopyRepository,
        private MovieSummaryResolverInterface $movieSummaryResolver,
    ) {}

    public function getByIds(array $physicalCopyIds): array
    {
        $copies = $this->physicalCopyRepository->findManyByUuids($physicalCopyIds);

        if ($copies === []) {
            return [];
        }

        $movieIds = array_values(array_map(
            fn (PhysicalCopy $copy) => $copy->movieId(),
            $copies,
        ));
        $movies = $this->movieSummaryResolver->resolveMany($movieIds);

        $result = [];

        foreach ($copies as $copyUuid => $copy) {
            $movieId = $copy->movieId()->value();
            $movie = $movies[$movieId] ?? null;

            $result[$copyUuid] = CopyDetails::create(
                $movieId,
                $movie?->title() ?? '',
                $copy->format()->value(),
                $copy->condition()->value(),
                $copy->price()->cents(),
                $copy->stockAvailable()->value(),
                $copy->isActive(),
                $movie?->posterPath(),
            );
        }

        return $result;
    }
}
