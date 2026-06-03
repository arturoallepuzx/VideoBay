<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Notification\Domain\ValueObject\NotificationType;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    public function test_lists_my_notifications_with_metadata(): void
    {
        $user = UserFactory::new()->create();
        $other = UserFactory::new()->create();
        $this->actingAsContext((string) $user->uuid);

        $notificationId = (string) Str::uuid();
        DB::table('notifications')->insert([
            [
                'uuid' => $notificationId,
                'user_id' => $user->id,
                'type' => NotificationType::REVIEW_LIKED,
                'title' => 'A alguien le gusto tu resena',
                'body' => 'The Matrix',
                'action_url' => '/movie/example',
                'metadata' => json_encode(['actor' => ['name' => 'Alice']], JSON_THROW_ON_ERROR),
                'read_at' => null,
                'created_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'user_id' => $other->id,
                'type' => NotificationType::ORDER_READY_FOR_PICKUP,
                'title' => 'Otra',
                'body' => null,
                'action_url' => null,
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'read_at' => null,
                'created_at' => now(),
            ],
        ]);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.uuid', $notificationId)
            ->assertJsonPath('items.0.metadata.actor.name', 'Alice');
    }

    public function test_mark_notification_as_read_is_idempotent(): void
    {
        $user = UserFactory::new()->create();
        $this->actingAsContext((string) $user->uuid);

        $notificationId = (string) Str::uuid();
        DB::table('notifications')->insert([
            'uuid' => $notificationId,
            'user_id' => $user->id,
            'type' => NotificationType::ORDER_READY_FOR_PICKUP,
            'title' => 'Tu pedido esta listo',
            'body' => null,
            'action_url' => '/orders',
            'metadata' => json_encode([], JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
        ]);

        $this->postJson('/api/notifications/'.$notificationId.'/read')->assertNoContent();
        $this->postJson('/api/notifications/'.$notificationId.'/read')->assertNoContent();

        $this->assertNotNull(DB::table('notifications')->where('uuid', $notificationId)->value('read_at'));
    }

    public function test_mark_all_notifications_as_read_is_idempotent(): void
    {
        $user = UserFactory::new()->create();
        $this->actingAsContext((string) $user->uuid);

        DB::table('notifications')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'type' => NotificationType::ORDER_READY_FOR_PICKUP,
            'title' => 'Tu pedido esta listo',
            'body' => null,
            'action_url' => '/orders',
            'metadata' => json_encode([], JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
        ]);

        $this->postJson('/api/notifications/read-all')->assertNoContent();
        $this->postJson('/api/notifications/read-all')->assertNoContent();

        $this->assertSame(0, DB::table('notifications')->where('user_id', $user->id)->whereNull('read_at')->count());
    }
}
