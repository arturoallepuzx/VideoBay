<?php

declare(strict_types=1);

namespace App\Catalog\Application\SearchPeople;

use App\Catalog\Domain\Exception\TmdbServiceUnavailableException;
use App\Catalog\Domain\Interfaces\PersonRepositoryInterface;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;

class SearchPeople
{
    private const FALLBACK_PER_PAGE = 20;

    public function __construct(
        private TmdbClientInterface $tmdbClient,
        private PersonRepositoryInterface $personRepository,
    ) {}

    public function __invoke(string $query, int $page = 1): SearchPeopleResponse
    {
        $trimmed = trim($query);

        if ($trimmed === '') {
            return SearchPeopleResponse::empty($page);
        }

        if ($page < 1) {
            $page = 1;
        }

        try {
            $tmdbResult = $this->tmdbClient->searchPeople($trimmed, $page);

            return SearchPeopleResponse::fromTmdb($tmdbResult);
        } catch (TmdbServiceUnavailableException) {
            $fallback = $this->personRepository->searchByFulltext($trimmed, $page, self::FALLBACK_PER_PAGE);

            return SearchPeopleResponse::fromLocalFallback($fallback);
        }
    }
}
