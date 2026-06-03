<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\ValueObject\CreditDepartment;
use App\Shared\Domain\ValueObject\Uuid;

class MovieCredit
{
    private function __construct(
        private ?int $id,
        private Uuid $movieId,
        private Uuid $personId,
        private CreditDepartment $department,
        private ?string $job,
        private ?string $characterName,
        private ?int $creditOrder,
    ) {}

    public static function dddCreate(
        Uuid $movieId,
        Uuid $personId,
        CreditDepartment $department,
        ?string $job = null,
        ?string $characterName = null,
        ?int $creditOrder = null,
    ): self {
        return new self(null, $movieId, $personId, $department, $job, $characterName, $creditOrder);
    }

    public static function fromPersistence(
        int $id,
        string $movieId,
        string $personId,
        string $department,
        ?string $job,
        ?string $characterName,
        ?int $creditOrder,
    ): self {
        return new self(
            $id,
            Uuid::create($movieId),
            Uuid::create($personId),
            CreditDepartment::create($department),
            $job,
            $characterName,
            $creditOrder,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function personId(): Uuid
    {
        return $this->personId;
    }

    public function department(): CreditDepartment
    {
        return $this->department;
    }

    public function job(): ?string
    {
        return $this->job;
    }

    public function characterName(): ?string
    {
        return $this->characterName;
    }

    public function creditOrder(): ?int
    {
        return $this->creditOrder;
    }

    public function isCast(): bool
    {
        return $this->characterName !== null;
    }

    public function isCrew(): bool
    {
        return $this->job !== null;
    }
}
