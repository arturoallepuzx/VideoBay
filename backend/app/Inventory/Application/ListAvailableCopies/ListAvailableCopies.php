<?php

declare(strict_types=1);

namespace App\Inventory\Application\ListAvailableCopies;

use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\Interfaces\MovieSummaryResolverInterface;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ListAvailableCopies
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private PhysicalCopyRepositoryInterface $physicalCopyRepository,
        private MovieSummaryResolverInterface $movieSummaryResolver,
    ) {}

    public function __invoke(?string $movieUuid, int $page, int $perPage): ListAvailableCopiesResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $movieId = $movieUuid !== null ? Uuid::create($movieUuid) : null;

        $result = $this->physicalCopyRepository->listAvailable($movieId, $page, $perPage);

        $movieUuids = $this->collectMovieUuids($result['copies']);
        $movieSummaries = $this->movieSummaryResolver->resolveMany($movieUuids);

        return ListAvailableCopiesResponse::create($result, $movieSummaries);
    }

    /**
     * @param  list<PhysicalCopy>  $copies
     * @return list<Uuid>
     */
    private function collectMovieUuids(array $copies): array
    {
        $seen = [];
        $uuids = [];

        foreach ($copies as $copy) {
            $value = $copy->movieId()->value();
            if (isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $uuids[] = $copy->movieId();
        }

        return $uuids;
    }
}
