<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MysqlUniqueConstraintViolationDetector;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Exception\UserNameAlreadyExistsException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\QueryException;

class EloquentUserRepository implements UserRepositoryInterface
{
    private const UNIQUE_USER_EMAIL_CONSTRAINT = 'users_email_active_unique';

    private const UNIQUE_USER_NAME_CONSTRAINT = 'users_name_active_unique';

    public function __construct(
        private EloquentUser $model,
        private MysqlUniqueConstraintViolationDetector $uniqueConstraintViolationDetector,
    ) {}

    public function create(User $user): void
    {
        try {
            $this->model->newQuery()->create([
                'uuid' => $user->id()->value(),
                'role' => $user->role()->value(),
                'name' => $user->name()->value(),
                'email' => $user->email()->value(),
                'email_verified_at' => $user->emailVerifiedAt()?->value(),
                'password_hash' => $user->passwordHash()->value(),
                'avatar_url' => $user->avatarUrl()?->value(),
                'accessibility_settings' => $user->accessibilitySettings()->toArray(),
                'created_at' => $user->createdAt()->value(),
                'updated_at' => $user->updatedAt()->value(),
            ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_USER_EMAIL_CONSTRAINT)) {
                throw UserEmailAlreadyExistsException::forEmail($user->email()->value());
            }

            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_USER_NAME_CONSTRAINT)) {
                throw UserNameAlreadyExistsException::forName($user->name()->value());
            }

            throw $e;
        }
    }

    public function update(User $user): void
    {
        try {
            $this->model->newQuery()
                ->where('uuid', $user->id()->value())
                ->update([
                    'role' => $user->role()->value(),
                    'name' => $user->name()->value(),
                    'email' => $user->email()->value(),
                    'email_verified_at' => $user->emailVerifiedAt()?->value(),
                    'password_hash' => $user->passwordHash()->value(),
                    'avatar_url' => $user->avatarUrl()?->value(),
                    'accessibility_settings' => $user->accessibilitySettings()->toArray(),
                    'updated_at' => $user->updatedAt()->value(),
                ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_USER_EMAIL_CONSTRAINT)) {
                throw UserEmailAlreadyExistsException::forEmail($user->email()->value());
            }

            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_USER_NAME_CONSTRAINT)) {
                throw UserNameAlreadyExistsException::forName($user->name()->value());
            }

            throw $e;
        }
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('uuid', $id->value())
            ->delete();
    }

    public function findById(Uuid $id): ?User
    {
        $model = $this->model->newQuery()
            ->where('uuid', $id->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByEmail(Email $email): ?User
    {
        $model = $this->model->newQuery()
            ->where('email', $email->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->model->newQuery()
            ->where('email', $email->value())
            ->exists();
    }

    public function existsByEmailExcludingId(Email $email, Uuid $excludeUserId): bool
    {
        return $this->model->newQuery()
            ->where('email', $email->value())
            ->where('uuid', '!=', $excludeUserId->value())
            ->exists();
    }

    public function findAll(): array
    {
        return $this->model->newQuery()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (EloquentUser $model): User => $this->toDomainEntity($model))
            ->all();
    }

    private function toDomainEntity(EloquentUser $model): User
    {
        return User::fromPersistence(
            $model->uuid,
            $model->role,
            $model->name,
            $model->email,
            $model->password_hash,
            $model->avatar_url,
            $model->email_verified_at,
            $model->accessibility_settings ?? [],
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
