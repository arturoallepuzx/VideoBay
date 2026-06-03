<?php

declare(strict_types=1);

namespace Tests\Feature\Streaming;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Infrastructure\Persistence\Models\EloquentVideoFile;
use Database\Factories\VideoFileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class ServeVideoRangeTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    private function readyVideoOnDisk(int $bytes = 4096): EloquentVideoFile
    {
        $path = tempnam(sys_get_temp_dir(), 'vbtest_').'.mp4';
        file_put_contents($path, str_repeat('A', $bytes));
        $this->tempFiles[] = $path;

        return VideoFileFactory::new()->create([
            'processed_path' => $path,
            'file_size_bytes' => $bytes,
        ]);
    }

    public function test_serves_partial_content_for_range_request(): void
    {
        $this->actingAsContext();
        $video = $this->readyVideoOnDisk(4096);

        $response = $this->withHeaders(['Range' => 'bytes=0-1023'])
            ->get('/api/stream/'.$video->uuid);

        $response->assertStatus(206);
        $response->assertHeader('Accept-Ranges', 'bytes');
        $response->assertHeader('Content-Range', 'bytes 0-1023/4096');
        $response->assertHeader('Content-Type', 'video/mp4');
    }

    public function test_serves_full_content_without_range(): void
    {
        $this->actingAsContext();
        $video = $this->readyVideoOnDisk(2048);

        $response = $this->get('/api/stream/'.$video->uuid);

        $response->assertStatus(200);
        $response->assertHeader('Accept-Ranges', 'bytes');
        $response->assertHeader('Content-Type', 'video/mp4');
    }

    public function test_requires_authentication(): void
    {
        $video = $this->readyVideoOnDisk();

        $response = $this->getJson('/api/stream/'.$video->uuid);

        $response->assertStatus(401);
    }

    public function test_returns_404_when_soft_deleted(): void
    {
        $this->actingAsContext();
        $video = $this->readyVideoOnDisk();
        $video->delete();

        $response = $this->get('/api/stream/'.$video->uuid);

        $response->assertStatus(404);
    }

    public function test_returns_409_when_not_ready(): void
    {
        $this->actingAsContext();
        $video = VideoFileFactory::new()->pending()->create();

        $response = $this->get('/api/stream/'.$video->uuid);

        $response->assertStatus(409);
    }

    public function test_returns_404_for_unknown_video(): void
    {
        $this->actingAsContext();

        $response = $this->get('/api/stream/'.Uuid::generate()->value());

        $response->assertStatus(404);
    }
}
