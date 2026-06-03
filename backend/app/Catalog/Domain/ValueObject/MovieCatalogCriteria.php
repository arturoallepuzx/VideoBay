<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

class MovieCatalogCriteria
{
    public const SORT_NEWEST = 'newest';

    public const SORT_TITLE = 'title';

    public const SORT_RATING = 'rating';

    private const VALID_SORTS = [self::SORT_NEWEST, self::SORT_TITLE, self::SORT_RATING];

    private const DEFAULT_PER_PAGE = 24;

    private const MAX_PER_PAGE = 100;

    private function __construct(
        private ?string $genre,
        private ?int $yearFrom,
        private ?int $yearTo,
        private string $sort,
        private int $page,
        private int $perPage,
    ) {}

    public static function create(
        ?string $genre,
        ?int $yearFrom,
        ?int $yearTo,
        ?string $sort,
        int $page,
        int $perPage,
    ): self {
        $sort ??= self::SORT_NEWEST;

        if (! in_array($sort, self::VALID_SORTS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid catalog sort "%s". Allowed: %s.', $sort, implode(', ', self::VALID_SORTS))
            );
        }

        $genre = $genre !== null && trim($genre) !== '' ? trim($genre) : null;
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        return new self($genre, $yearFrom, $yearTo, $sort, $page, $perPage);
    }

    public function genre(): ?string
    {
        return $this->genre;
    }

    public function yearFrom(): ?int
    {
        return $this->yearFrom;
    }

    public function yearTo(): ?int
    {
        return $this->yearTo;
    }

    public function sort(): string
    {
        return $this->sort;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }
}
