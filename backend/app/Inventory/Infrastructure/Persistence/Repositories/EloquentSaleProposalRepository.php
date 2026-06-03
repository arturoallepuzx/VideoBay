<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Repositories;

use App\Inventory\Domain\Entity\SaleProposal;
use App\Inventory\Domain\Interfaces\SaleProposalRepositoryInterface;
use App\Inventory\Domain\ValueObject\SaleProposalStatus;
use App\Inventory\Infrastructure\Persistence\Models\EloquentSaleProposal;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MovieIdResolverInterface;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use Illuminate\Database\Eloquent\Builder;

class EloquentSaleProposalRepository implements SaleProposalRepositoryInterface
{
    private string $currency;

    public function __construct(
        private EloquentSaleProposal $model,
        private MovieIdResolverInterface $movieIdResolver,
        private UserIdResolverInterface $userIdResolver,
        SystemCurrencyProviderInterface $currencyProvider,
    ) {
        $this->currency = $currencyProvider->getCurrency();
    }

    public function create(SaleProposal $proposal): void
    {
        $this->model->newQuery()->create([
            'uuid' => $proposal->id()->value(),
            'user_id' => $this->userIdResolver->toInternalId($proposal->userId()),
            'movie_id' => $proposal->movieId() !== null
                ? $this->movieIdResolver->toInternalId($proposal->movieId())
                : null,
            'title_text' => $proposal->titleText(),
            'barcode' => $proposal->barcode()?->value(),
            'format' => $proposal->format()->value(),
            'condition' => $proposal->condition()->value(),
            'notes' => $proposal->notes(),
            'offered_price_cents' => $proposal->offeredPrice()?->cents(),
            'status' => $proposal->status()->value(),
            'created_at' => $proposal->createdAt()->value(),
            'updated_at' => $proposal->updatedAt()->value(),
        ]);
    }

    public function update(SaleProposal $proposal): void
    {
        $this->model->newQuery()
            ->where('uuid', $proposal->id()->value())
            ->update([
                'status' => $proposal->status()->value(),
                'updated_at' => $proposal->updatedAt()->value(),
            ]);
    }

    public function findByUuid(Uuid $uuid): ?SaleProposal
    {
        $row = $this->model->newQuery()
            ->leftJoin('users', 'sale_proposals.user_id', '=', 'users.id')
            ->leftJoin('movies', 'sale_proposals.movie_id', '=', 'movies.id')
            ->where('sale_proposals.uuid', $uuid->value())
            ->select([
                'sale_proposals.*',
                'users.uuid as user_uuid',
                'movies.uuid as movie_uuid',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->toDomainEntity(
            $row,
            (string) $row->getAttribute('user_uuid'),
            $row->getAttribute('movie_uuid') !== null ? (string) $row->getAttribute('movie_uuid') : null,
        );
    }

    public function findByUuidForUpdate(Uuid $uuid): ?SaleProposal
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        $userUuid = $this->userIdResolver->toDomainUuid((int) $model->getAttribute('user_id'))->value();

        $movieId = $model->getAttribute('movie_id');
        $movieUuid = $movieId !== null
            ? $this->movieIdResolver->toDomainUuid((int) $movieId)->value()
            : null;

        return $this->toDomainEntity($model, $userUuid, $movieUuid);
    }

    public function listByUser(Uuid $userId, int $page, int $perPage): array
    {
        $base = $this->model->newQuery()
            ->leftJoin('users', 'sale_proposals.user_id', '=', 'users.id')
            ->leftJoin('movies', 'sale_proposals.movie_id', '=', 'movies.id')
            ->where('users.uuid', $userId->value());

        return $this->paginate($base, $page, $perPage);
    }

    public function listPending(int $page, int $perPage): array
    {
        $base = $this->model->newQuery()
            ->leftJoin('users', 'sale_proposals.user_id', '=', 'users.id')
            ->leftJoin('movies', 'sale_proposals.movie_id', '=', 'movies.id')
            ->where('sale_proposals.status', SaleProposalStatus::proposed()->value());

        return $this->paginate($base, $page, $perPage);
    }

    /**
     * @param  Builder<EloquentSaleProposal>  $base
     * @return array{proposals: list<SaleProposal>, total: int, page: int, totalPages: int}
     */
    private function paginate($base, int $page, int $perPage): array
    {
        $total = (clone $base)->count('sale_proposals.id');

        $rows = $base
            ->select([
                'sale_proposals.*',
                'users.uuid as user_uuid',
                'movies.uuid as movie_uuid',
            ])
            ->orderBy('sale_proposals.created_at', 'desc')
            ->forPage($page, $perPage)
            ->get();

        return [
            'proposals' => $rows
                ->map(fn (EloquentSaleProposal $m): SaleProposal => $this->toDomainEntity(
                    $m,
                    (string) $m->getAttribute('user_uuid'),
                    $m->getAttribute('movie_uuid') !== null ? (string) $m->getAttribute('movie_uuid') : null,
                ))
                ->all(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    private function toDomainEntity(
        EloquentSaleProposal $model,
        string $userUuid,
        ?string $movieUuid,
    ): SaleProposal {
        return SaleProposal::fromPersistence(
            (string) $model->uuid,
            $userUuid,
            $movieUuid,
            $model->title_text !== null ? (string) $model->title_text : null,
            $model->barcode !== null ? (string) $model->barcode : null,
            (string) $model->format,
            (string) $model->condition,
            $model->notes !== null ? (string) $model->notes : null,
            $model->offered_price_cents !== null ? (int) $model->offered_price_cents : null,
            $this->currency,
            (string) $model->status,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
