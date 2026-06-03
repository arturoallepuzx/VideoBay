<?php

declare(strict_types=1);

namespace Tests\Unit\Review;

use App\Review\Domain\Entity\Review;
use App\Review\Domain\ValueObject\ReviewBody;
use App\Review\Domain\ValueObject\ReviewRating;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class ReviewTest extends TestCase
{
    private function makeReview(?ReviewBody $body = null, bool $containsSpoilers = false): Review
    {
        return Review::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            ReviewRating::create(8),
            $body ?? ReviewBody::create('Buena película'),
            $containsSpoilers,
        );
    }

    public function test_ddd_create_starts_with_zero_likes_and_not_deleted(): void
    {
        $review = $this->makeReview();

        $this->assertSame(0, $review->likesCount());
        $this->assertFalse($review->isDeleted());
        $this->assertFalse($review->wasModified());
    }

    public function test_update_rating_to_different_value_marks_modified(): void
    {
        $review = $this->makeReview();

        $review->updateRating(ReviewRating::create(10));

        $this->assertSame(10, $review->rating()->value());
        $this->assertTrue($review->wasModified());
    }

    public function test_update_rating_to_same_value_does_not_mark_modified(): void
    {
        $review = $this->makeReview();

        $review->updateRating(ReviewRating::create(8));

        $this->assertFalse($review->wasModified());
    }

    public function test_update_body_from_value_to_null_marks_modified(): void
    {
        $review = $this->makeReview(ReviewBody::create('Texto inicial'));

        $review->updateBody(null);

        $this->assertNull($review->body());
        $this->assertTrue($review->wasModified());
    }

    public function test_update_body_from_null_to_null_is_noop(): void
    {
        $review = Review::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            ReviewRating::create(5),
            null,
            false,
        );

        $review->updateBody(null);

        $this->assertFalse($review->wasModified());
    }

    public function test_increment_likes_increases_counter_and_marks_modified(): void
    {
        $review = $this->makeReview();

        $review->incrementLikes();
        $review->incrementLikes();

        $this->assertSame(2, $review->likesCount());
        $this->assertTrue($review->wasModified());
    }

    public function test_decrement_likes_does_not_go_below_zero(): void
    {
        $review = $this->makeReview();

        $review->decrementLikes();

        $this->assertSame(0, $review->likesCount());
        $this->assertFalse($review->wasModified());
    }

    public function test_soft_delete_sets_deleted_at_and_marks_modified(): void
    {
        $review = $this->makeReview();

        $review->softDelete();

        $this->assertTrue($review->isDeleted());
        $this->assertNotNull($review->deletedAt());
        $this->assertTrue($review->wasModified());
    }

    public function test_soft_delete_is_idempotent(): void
    {
        $review = $this->makeReview();
        $review->softDelete();

        $reviewAlreadyDeleted = $review;
        $reviewAlreadyDeleted->softDelete();

        $this->assertTrue($reviewAlreadyDeleted->isDeleted());
    }

    public function test_is_owned_by_returns_true_only_for_author(): void
    {
        $authorId = Uuid::generate();
        $other = Uuid::generate();

        $review = Review::dddCreate(
            $authorId,
            Uuid::generate(),
            ReviewRating::create(7),
            null,
            false,
        );

        $this->assertTrue($review->isOwnedBy($authorId));
        $this->assertFalse($review->isOwnedBy($other));
    }
}
