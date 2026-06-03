<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Interfaces\StripeWebhookEventStoreInterface;
use App\Order\Domain\ValueObject\WebhookEvent;
use App\Order\Infrastructure\Persistence\Models\EloquentStripeWebhookEvent;
use App\Shared\Infrastructure\Persistence\MysqlUniqueConstraintViolationDetector;
use Illuminate\Database\QueryException;

class EloquentStripeWebhookEventStore implements StripeWebhookEventStoreInterface
{
    private const PRIMARY_KEY_CONSTRAINT = 'PRIMARY';

    public function __construct(
        private EloquentStripeWebhookEvent $model,
        private MysqlUniqueConstraintViolationDetector $uniqueConstraintViolationDetector,
    ) {}

    public function shouldProcess(WebhookEvent $event): bool
    {
        $existing = $this->model->newQuery()->whereKey($event->id())->first();

        if ($existing !== null) {
            return $existing->processed_at === null;
        }

        try {
            $this->model->newQuery()->create([
                'id' => $event->id(),
                'type' => $event->type(),
                'payload' => $event->payload(),
                'created_at' => now(),
            ]);

            return true;
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::PRIMARY_KEY_CONSTRAINT)) {
                return false;
            }

            throw $e;
        }
    }

    public function markProcessed(string $eventId): void
    {
        $this->model->newQuery()
            ->whereKey($eventId)
            ->update(['processed_at' => now()]);
    }

    public function markFailed(string $eventId, string $error): void
    {
        $this->model->newQuery()
            ->whereKey($eventId)
            ->update(['processing_error' => $error]);
    }
}
