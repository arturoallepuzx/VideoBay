<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Interfaces;

use App\Catalog\Domain\Entity\MovieCredit;
use App\Shared\Domain\ValueObject\Uuid;

interface MovieCreditRepositoryInterface
{
    public function create(MovieCredit $credit): void;

    public function deleteByMovieUuid(Uuid $movieUuid): void;

    /**
     * @return list<MovieCredit>
     */
    public function findByMovieUuid(Uuid $movieUuid): array;

    /**
     * @return list<MovieCredit>
     */
    public function findByPersonUuid(Uuid $personUuid): array;
}
