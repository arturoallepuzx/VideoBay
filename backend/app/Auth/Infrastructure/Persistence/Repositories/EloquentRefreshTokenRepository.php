<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence\Repositories;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\RefreshTokenHash;
use App\Auth\Infrastructure\Persistence\Models\EloquentRefreshToken;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private EloquentRefreshToken $model,
        private UserIdResolverInterface $userIdResolver,
    ) {}

    public function create(RefreshToken $refreshToken): void
    {
        $this->model->newQuery()->create([
            'uuid' => $refreshToken->id()->value(),
            'session_uuid' => $refreshToken->sessionId()->value(),
            'user_id' => $this->userIdResolver->toInternalId($refreshToken->userId()),
            'token_hash' => $refreshToken->tokenHash()->value(),
            'expires_at' => $refreshToken->expiresAt()->value(),
            'revoked_at' => $refreshToken->revokedAt()?->value(),
            'replaced_by_id' => $refreshToken->replacedById() !== null
                ? $this->resolveInternalId($refreshToken->replacedById())
                : null,
            'created_at' => $refreshToken->createdAt()->value(),
            'updated_at' => $refreshToken->updatedAt()->value(),
        ]);
    }

    public function findByTokenHash(RefreshTokenHash $tokenHash): ?RefreshToken
    {
        $model = $this->model->newQuery()
            ->where('token_hash', $tokenHash->value())
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function update(RefreshToken $refreshToken): void
    {
        $this->model->newQuery()
            ->where('uuid', $refreshToken->id()->value())
            ->update([
                'revoked_at' => $refreshToken->revokedAt()?->value(),
                'replaced_by_id' => $refreshToken->replacedById() !== null
                    ? $this->resolveInternalId($refreshToken->replacedById())
                    : null,
                'updated_at' => $refreshToken->updatedAt()->value(),
            ]);
    }

    public function revokeAllInSession(Uuid $sessionId): void
    {
        $now = DomainDateTime::now()->value();

        $this->model->newQuery()
            ->where('session_uuid', $sessionId->value())
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function revokeAllByUserId(Uuid $userId): void
    {
        $now = DomainDateTime::now()->value();

        try {
            $internalUserId = $this->userIdResolver->toInternalId($userId);
        } catch (ModelNotFoundException) {
            return;
        }

        $this->model->newQuery()
            ->where('user_id', $internalUserId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function countActiveSessionsByUserId(Uuid $userId): int
    {
        try {
            $internalUserId = $this->userIdResolver->toInternalId($userId);
        } catch (ModelNotFoundException) {
            return 0;
        }

        return (int) $this->model->newQuery()
            ->where('user_id', $internalUserId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', DomainDateTime::now()->value())
            ->distinct()
            ->count('session_uuid');
    }

    public function revokeOldestActiveSessionsByUserId(Uuid $userId, int $howMany): void
    {
        if ($howMany <= 0) {
            return;
        }

        try {
            $internalUserId = $this->userIdResolver->toInternalId($userId);
        } catch (ModelNotFoundException) {
            return;
        }

        $now = DomainDateTime::now()->value();

        $oldestSessionIds = $this->model->newQuery()
            ->where('user_id', $internalUserId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', $now)
            ->selectRaw('session_uuid, MIN(created_at) as session_started_at')
            ->groupBy('session_uuid')
            ->orderBy('session_started_at', 'asc')
            ->limit($howMany)
            ->lockForUpdate()
            ->pluck('session_uuid')
            ->all();

        if ($oldestSessionIds === []) {
            return;
        }

        $this->model->newQuery()
            ->where('user_id', $internalUserId)
            ->whereIn('session_uuid', $oldestSessionIds)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $now,
                'updated_at' => $now,
            ]);
    }

    private function toDomainEntity(EloquentRefreshToken $model): RefreshToken
    {
        $replacedById = $model->replaced_by_id !== null
            ? $this->resolveDomainUuid((int) $model->replaced_by_id)
            : null;

        return RefreshToken::fromPersistence(
            $model->uuid,
            $this->userIdResolver->toDomainUuid((int) $model->user_id)->value(),
            $model->session_uuid,
            $model->token_hash,
            $model->expires_at->toDateTimeImmutable(),
            $model->revoked_at?->toDateTimeImmutable(),
            $replacedById,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }

    private function resolveInternalId(Uuid $refreshTokenUuid): int
    {
        $id = $this->model->newQuery()
            ->where('uuid', $refreshTokenUuid->value())
            ->value('id');

        if ($id === null) {
            throw (new ModelNotFoundException)->setModel(EloquentRefreshToken::class, [$refreshTokenUuid->value()]);
        }

        return (int) $id;
    }

    private function resolveDomainUuid(int $internalId): string
    {
        $uuid = $this->model->newQuery()
            ->where('id', $internalId)
            ->value('uuid');

        if ($uuid === null) {
            throw (new ModelNotFoundException)->setModel(EloquentRefreshToken::class, [$internalId]);
        }

        return (string) $uuid;
    }
}
