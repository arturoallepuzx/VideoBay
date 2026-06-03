<?php

declare(strict_types=1);

namespace Tests\Feature\Streaming;

use App\Shared\Domain\ValueObject\Uuid;
use Database\Factories\VideoFileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class DeleteVideoFileTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    public function test_admin_takedown_returns_204_and_deletes_physical_file(): void
    {
        $this->actingAsAdmin();
        $path = tempnam(sys_get_temp_dir(), 'vbtest_').'.mp4';
        file_put_contents($path, 'data');
        $video = VideoFileFactory::new()->create(['processed_path' => $path]);

        $this->assertFileExists($path);

        $response = $this->delete('/api/admin/videos/'.$video->uuid);

        $response->assertStatus(204);
        $this->assertFileDoesNotExist($path);
    }

    public function test_delete_unknown_video_returns_404(): void
    {
        $this->actingAsAdmin();

        $response = $this->deleteJson('/api/admin/videos/'.Uuid::generate()->value());

        $response->assertStatus(404);
    }

    public function test_requires_authentication(): void
    {
        $this->withoutCsrfMiddleware();
        $video = VideoFileFactory::new()->create();

        $response = $this->deleteJson('/api/admin/videos/'.$video->uuid);

        $response->assertStatus(401);
    }
}
