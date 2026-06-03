<?php

declare(strict_types=1);

namespace App\User\Application\ListUsersWithActiveSessions;

use App\User\Domain\ValueObject\UserActiveSessionsSummary;

final class ListUsersWithActiveSessionsResponse
{
    /**
     * @param  list<UserActiveSessionsSummary>  $summaries
     */
    private function __construct(
        private array $summaries,
    ) {}

    /**
     * @param  list<UserActiveSessionsSummary>  $summaries
     */
    public static function create(array $summaries): self
    {
        return new self($summaries);
    }

    /**
     * @return array{users: list<array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     email_verified_at: string|null,
     *     role: string,
     *     avatar_url: string|null,
     *     active_sessions: int,
     *     last_seen_at: string
     * }>}
     */
    public function toArray(): array
    {
        return [
            'users' => array_map(
                fn (UserActiveSessionsSummary $summary): array => [
                    'id' => $summary->userId()->value(),
                    'name' => $summary->name()->value(),
                    'email' => $summary->email()->value(),
                    'email_verified_at' => $summary->emailVerifiedAt()?->format(\DateTimeInterface::ATOM),
                    'role' => $summary->role()->value(),
                    'avatar_url' => $summary->avatarUrl()?->value(),
                    'active_sessions' => $summary->activeSessions(),
                    'last_seen_at' => $summary->lastSeenAt()->format(\DateTimeInterface::ATOM),
                ],
                $this->summaries,
            ),
        ];
    }
}
