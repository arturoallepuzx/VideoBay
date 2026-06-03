<?php

declare(strict_types=1);

namespace App\Notification\Domain\ValueObject;

class NotificationType
{
    public const ORDER_READY_FOR_PICKUP = 'order.ready_for_pickup';

    public const PROPOSAL_ACCEPTED = 'proposal.accepted';

    public const PROPOSAL_REJECTED = 'proposal.rejected';

    public const REVIEW_LIKED = 'review.liked';

    public const REVIEW_REMOVED_BY_MODERATION = 'review.removed_by_moderation';

    public const SUBTITLE_REMOVED_BY_MODERATION = 'subtitle.removed_by_moderation';

    public const WISHLIST_NOW_IN_STOCK = 'wishlist.now_in_stock';

    public const WATCH_LATER_NOW_STREAMABLE = 'watch_later.now_streamable';

    private const VALID_TYPES = [
        self::ORDER_READY_FOR_PICKUP,
        self::PROPOSAL_ACCEPTED,
        self::PROPOSAL_REJECTED,
        self::REVIEW_LIKED,
        self::REVIEW_REMOVED_BY_MODERATION,
        self::SUBTITLE_REMOVED_BY_MODERATION,
        self::WISHLIST_NOW_IN_STOCK,
        self::WATCH_LATER_NOW_STREAMABLE,
    ];

    private function __construct(private string $value) {}

    public static function create(string $value): self
    {
        $normalized = trim($value);

        if (! in_array($normalized, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid notification type "%s".', $value));
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
