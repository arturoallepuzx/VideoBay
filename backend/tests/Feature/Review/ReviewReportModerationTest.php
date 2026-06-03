<?php

declare(strict_types=1);

namespace Tests\Feature\Review;

use Database\Factories\MovieFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class ReviewReportModerationTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    public function test_resolving_one_report_resolves_pending_reports_for_same_review(): void
    {
        $this->actingAsAdmin();

        $author = UserFactory::new()->create();
        $firstReporter = UserFactory::new()->create();
        $secondReporter = UserFactory::new()->create();
        $movie = MovieFactory::new()->create();
        $now = now();

        $reviewUuid = (string) Str::uuid();
        $reviewId = DB::table('reviews')->insertGetId([
            'uuid' => $reviewUuid,
            'user_id' => $author->id,
            'movie_id' => $movie->id,
            'rating' => 8,
            'body' => 'Reported review',
            'contains_spoilers' => false,
            'likes_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $firstReportId = DB::table('review_reports')->insertGetId([
            'review_id' => $reviewId,
            'reported_by_user_id' => $firstReporter->id,
            'reason' => 'spam',
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $secondReportId = DB::table('review_reports')->insertGetId([
            'review_id' => $reviewId,
            'reported_by_user_id' => $secondReporter->id,
            'reason' => 'offensive',
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->postJson('/api/admin/review-reports/'.$firstReportId.'/resolve', [
            'decision' => 'resolved',
        ])
            ->assertOk()
            ->assertJsonPath('report_id', $firstReportId)
            ->assertJsonPath('review_id', $reviewUuid)
            ->assertJsonPath('status', 'resolved');

        $this->assertSoftDeleted('reviews', ['id' => $reviewId]);
        $this->assertDatabaseHas('review_reports', ['id' => $firstReportId, 'status' => 'resolved']);
        $this->assertDatabaseHas('review_reports', ['id' => $secondReportId, 'status' => 'resolved']);

        $this->getJson('/api/admin/review-reports')
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('items', []);
    }
}
