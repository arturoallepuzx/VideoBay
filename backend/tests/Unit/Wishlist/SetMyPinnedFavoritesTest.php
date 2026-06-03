<?php

declare(strict_types=1);

namespace Tests\Unit\Wishlist;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Application\ListMyPinnedFavorites\ListMyPinnedFavoritesResponse;
use App\Wishlist\Application\SetMyPinnedFavorites\SetMyPinnedFavorites;
use App\Wishlist\Domain\Entity\PinnedFavorite;
use App\Wishlist\Domain\Exception\InvalidPinnedSlotsException;
use App\Wishlist\Domain\Interfaces\MovieListItemResolverInterface;
use App\Wishlist\Domain\Interfaces\PinnedFavoriteRepositoryInterface;
use App\Wishlist\Domain\ValueObject\MovieListItemView;
use Mockery;
use PHPUnit\Framework\TestCase;

class SetMyPinnedFavoritesTest extends TestCase
{
    private const MAX_SLOTS = 5;

    private string $userUuid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userUuid = (string) Uuid::generate()->value();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_happy_path_replaces_all_for_user(): void
    {
        $movieA = (string) Uuid::generate()->value();
        $movieB = (string) Uuid::generate()->value();
        $slots = [
            ['position' => 1, 'movie_uuid' => $movieA],
            ['position' => 2, 'movie_uuid' => $movieB],
        ];

        $resolver = Mockery::mock(MovieListItemResolverInterface::class);
        $resolver->shouldReceive('resolveMany')
            ->andReturn([
                $movieA => MovieListItemView::create($movieA, 'Movie A', null, 2024),
                $movieB => MovieListItemView::create($movieB, 'Movie B', null, 2023),
            ]);

        $repository = Mockery::mock(PinnedFavoriteRepositoryInterface::class);
        $repository->shouldReceive('replaceAllForUser')->once();
        $repository->shouldReceive('listByUser')
            ->once()
            ->andReturn([
                PinnedFavorite::dddCreate(Uuid::create($this->userUuid), 1, Uuid::create($movieA)),
                PinnedFavorite::dddCreate(Uuid::create($this->userUuid), 2, Uuid::create($movieB)),
            ]);

        $useCase = new SetMyPinnedFavorites($repository, $resolver, self::MAX_SLOTS);
        $response = $useCase($this->userUuid, $slots);

        $this->assertInstanceOf(ListMyPinnedFavoritesResponse::class, $response);
        $this->assertCount(2, $response->slots);
    }

    public function test_empty_slots_clears_all_pins(): void
    {
        $resolver = Mockery::mock(MovieListItemResolverInterface::class);
        $resolver->shouldNotReceive('resolveMany')->withArgs(fn ($ids) => $ids !== []);
        $resolver->shouldReceive('resolveMany')->andReturn([]);

        $repository = Mockery::mock(PinnedFavoriteRepositoryInterface::class);
        $repository->shouldReceive('replaceAllForUser')->once();
        $repository->shouldReceive('listByUser')->once()->andReturn([]);

        $useCase = new SetMyPinnedFavorites($repository, $resolver, self::MAX_SLOTS);
        $response = $useCase($this->userUuid, []);

        $this->assertSame([], $response->slots);
    }

    public function test_throws_when_too_many_slots(): void
    {
        $slots = array_map(
            fn (int $i): array => ['position' => $i, 'movie_uuid' => (string) Uuid::generate()->value()],
            range(1, self::MAX_SLOTS + 1),
        );

        $useCase = $this->makeUseCase();

        $this->expectException(InvalidPinnedSlotsException::class);
        $this->expectExceptionMessage('Too many pinned slots');

        $useCase($this->userUuid, $slots);
    }

    public function test_throws_when_position_out_of_range(): void
    {
        $slots = [
            ['position' => self::MAX_SLOTS + 1, 'movie_uuid' => (string) Uuid::generate()->value()],
        ];

        $useCase = $this->makeUseCase();

        $this->expectException(InvalidPinnedSlotsException::class);
        $this->expectExceptionMessage('out of range');

        $useCase($this->userUuid, $slots);
    }

    public function test_throws_when_duplicate_position(): void
    {
        $slots = [
            ['position' => 1, 'movie_uuid' => (string) Uuid::generate()->value()],
            ['position' => 1, 'movie_uuid' => (string) Uuid::generate()->value()],
        ];

        $useCase = $this->makeUseCase();

        $this->expectException(InvalidPinnedSlotsException::class);
        $this->expectExceptionMessage('Duplicate pinned position');

        $useCase($this->userUuid, $slots);
    }

    public function test_throws_when_duplicate_movie(): void
    {
        $movieUuid = (string) Uuid::generate()->value();
        $slots = [
            ['position' => 1, 'movie_uuid' => $movieUuid],
            ['position' => 2, 'movie_uuid' => $movieUuid],
        ];

        $useCase = $this->makeUseCase();

        $this->expectException(InvalidPinnedSlotsException::class);
        $this->expectExceptionMessage('cannot be pinned in two slots');

        $useCase($this->userUuid, $slots);
    }

    public function test_throws_when_movie_not_found_in_resolver(): void
    {
        $movieUuid = (string) Uuid::generate()->value();
        $slots = [
            ['position' => 1, 'movie_uuid' => $movieUuid],
        ];

        $resolver = Mockery::mock(MovieListItemResolverInterface::class);
        $resolver->shouldReceive('resolveMany')->andReturn([]);

        $repository = Mockery::mock(PinnedFavoriteRepositoryInterface::class);
        $repository->shouldNotReceive('replaceAllForUser');

        $useCase = new SetMyPinnedFavorites($repository, $resolver, self::MAX_SLOTS);

        $this->expectException(InvalidPinnedSlotsException::class);
        $this->expectExceptionMessage('does not exist');

        $useCase($this->userUuid, $slots);
    }

    private function makeUseCase(): SetMyPinnedFavorites
    {
        $resolver = Mockery::mock(MovieListItemResolverInterface::class);
        $repository = Mockery::mock(PinnedFavoriteRepositoryInterface::class);

        return new SetMyPinnedFavorites($repository, $resolver, self::MAX_SLOTS);
    }
}
