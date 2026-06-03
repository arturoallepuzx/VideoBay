<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\Interfaces\AccessTokenVerifierInterface;
use App\Auth\Domain\Interfaces\RefreshTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Infrastructure\Persistence\Repositories\EloquentRefreshTokenRepository;
use App\Auth\Infrastructure\Services\FirebaseJwtAccessTokenIssuer;
use App\Auth\Infrastructure\Services\FirebaseJwtAccessTokenVerifier;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationGlobalRevoker;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationIssuer;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationRefresher;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationRevoker;
use App\Auth\Infrastructure\Services\RandomRefreshTokenIssuer;
use App\Catalog\Domain\Interfaces\BarcodeApiClientInterface;
use App\Catalog\Domain\Interfaces\BarcodeLookupRepositoryInterface;
use App\Catalog\Domain\Interfaces\GenreRepositoryInterface;
use App\Catalog\Domain\Interfaces\MovieCreditRepositoryInterface;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Catalog\Domain\Interfaces\PersonRepositoryInterface;
use App\Catalog\Domain\Interfaces\PurchasableMovieFinderInterface;
use App\Catalog\Domain\Interfaces\StreamableMovieFinderInterface;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use App\Catalog\Infrastructure\Listeners\RegisterBarcodeOnPhysicalCopyAdded;
use App\Catalog\Infrastructure\Listeners\RegisterBarcodeOnSaleProposalAccepted;
use App\Catalog\Infrastructure\Persistence\Repositories\EloquentBarcodeLookupRepository;
use App\Catalog\Infrastructure\Persistence\Repositories\EloquentGenreRepository;
use App\Catalog\Infrastructure\Persistence\Repositories\EloquentMovieCreditRepository;
use App\Catalog\Infrastructure\Persistence\Repositories\EloquentMovieRepository;
use App\Catalog\Infrastructure\Persistence\Repositories\EloquentPersonRepository;
use App\Catalog\Infrastructure\Query\EloquentPurchasableMovieFinder;
use App\Catalog\Infrastructure\Query\EloquentStreamableMovieFinder;
use App\Catalog\Infrastructure\Services\CachedTmdbClient;
use App\Catalog\Infrastructure\Services\LaravelHttpTmdbClient;
use App\Catalog\Infrastructure\Services\UpcDatabaseBarcodeApiClient;
use App\Inventory\Domain\Event\PhysicalCopyAdded;
use App\Inventory\Domain\Event\PhysicalCopyAvailableForSale;
use App\Inventory\Domain\Event\SaleProposalAccepted;
use App\Inventory\Domain\Event\SaleProposalResolved;
use App\Inventory\Domain\Interfaces\MovieSummaryResolverInterface as InventoryMovieSummaryResolverInterface;
use App\Inventory\Domain\Interfaces\MovieTitleResolverInterface;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Inventory\Domain\Interfaces\PricingSettingsRepositoryInterface;
use App\Inventory\Domain\Interfaces\SaleProposalRepositoryInterface;
use App\Inventory\Infrastructure\Persistence\Repositories\EloquentPhysicalCopyRepository;
use App\Inventory\Infrastructure\Persistence\Repositories\EloquentPricingSettingsRepository;
use App\Inventory\Infrastructure\Persistence\Repositories\EloquentSaleProposalRepository;
use App\Inventory\Infrastructure\Services\CatalogMovieSummaryResolver as InventoryCatalogMovieSummaryResolver;
use App\Inventory\Infrastructure\Services\CatalogMovieTitleResolver;
use App\Notification\Domain\Interfaces\NotificationRepositoryInterface;
use App\Notification\Infrastructure\Listeners\NotifyOrderReadyForPickup;
use App\Notification\Infrastructure\Listeners\NotifyReviewLiked;
use App\Notification\Infrastructure\Listeners\NotifyReviewRemovedByModeration;
use App\Notification\Infrastructure\Listeners\NotifySaleProposalResolved;
use App\Notification\Infrastructure\Listeners\NotifySubtitleRemovedByModeration;
use App\Notification\Infrastructure\Listeners\NotifyWatchLaterUsersWhenMovieStreamable;
use App\Notification\Infrastructure\Listeners\NotifyWishlistedUsersWhenMovieInStock;
use App\Notification\Infrastructure\Persistence\Repositories\EloquentNotificationRepository;
use App\Notification\Infrastructure\Services\DatabaseNotificationDispatcher;
use App\Order\Application\CreateCheckoutSession\CreateCheckoutSession;
use App\Order\Domain\Event\OrderReadyForPickup;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CheckoutGatewayInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\StockReservationInterface;
use App\Order\Domain\Interfaces\StripeWebhookEventStoreInterface;
use App\Order\Infrastructure\Console\CancelExpiredOrdersCommand;
use App\Order\Infrastructure\Persistence\Repositories\EloquentCartRepository;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use App\Order\Infrastructure\Persistence\Repositories\EloquentStripeWebhookEventStore;
use App\Order\Infrastructure\Services\InventoryCopyDetailsProvider;
use App\Order\Infrastructure\Services\InventoryStockReservation;
use App\Order\Infrastructure\Services\StripeCheckoutGateway;
use App\Review\Domain\Event\ReviewLiked;
use App\Review\Domain\Event\ReviewRemovedByModeration;
use App\Review\Domain\Interfaces\ReviewLikeRepositoryInterface;
use App\Review\Domain\Interfaces\ReviewReportRepositoryInterface;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Review\Domain\Interfaces\UserDisplayResolverInterface;
use App\Review\Infrastructure\Persistence\Repositories\EloquentReviewLikeRepository;
use App\Review\Infrastructure\Persistence\Repositories\EloquentReviewReportRepository;
use App\Review\Infrastructure\Persistence\Repositories\EloquentReviewRepository;
use App\Review\Infrastructure\Services\UserDisplayResolver;
use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\NotificationDispatcherInterface;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Shared\Infrastructure\Persistence\EloquentMovieIdResolver;
use App\Shared\Infrastructure\Persistence\EloquentPhysicalCopyIdResolver;
use App\Shared\Infrastructure\Persistence\EloquentUserIdResolver;
use App\Shared\Infrastructure\Persistence\MovieIdResolverInterface;
use App\Shared\Infrastructure\Persistence\PhysicalCopyIdResolverInterface;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use App\Shared\Infrastructure\Services\ConfigSystemCurrencyProvider;
use App\Shared\Infrastructure\Services\LaravelDomainEventDispatcher;
use App\Shared\Infrastructure\Services\LaravelTransactionRunner;
use App\Streaming\Application\CleanupFailedOriginals\CleanupFailedOriginals;
use App\Streaming\Application\TranscodeVideo\TranscodeVideo;
use App\Streaming\Domain\Event\VideoFileReady;
use App\Streaming\Domain\Interfaces\MovieResolverForStreamingInterface;
use App\Streaming\Domain\Interfaces\MovieSummaryResolverInterface;
use App\Streaming\Domain\Interfaces\PlaybackProgressRepositoryInterface;
use App\Streaming\Domain\Interfaces\TranscodingJobDispatcherInterface;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoTranscoderInterface;
use App\Streaming\Infrastructure\Console\CleanupFailedOriginalsCommand;
use App\Streaming\Infrastructure\Persistence\Repositories\EloquentPlaybackProgressRepository;
use App\Streaming\Infrastructure\Persistence\Repositories\EloquentVideoFileRepository;
use App\Streaming\Infrastructure\Services\CatalogMovieResolverForStreaming;
use App\Streaming\Infrastructure\Services\CatalogMovieSummaryResolver;
use App\Streaming\Infrastructure\Services\FfmpegVideoTranscoder;
use App\Streaming\Infrastructure\Services\LaravelTranscodingJobDispatcher;
use App\Streaming\Infrastructure\Services\LocalVideoFileStorage;
use App\Subtitle\Domain\Event\SubtitleRemovedByModeration;
use App\Subtitle\Domain\Interfaces\ExternalSubtitleProviderInterface;
use App\Subtitle\Domain\Interfaces\SubtitleConverterInterface;
use App\Subtitle\Domain\Interfaces\SubtitleFileStorageInterface;
use App\Subtitle\Domain\Interfaces\SubtitleMovieResolverInterface;
use App\Subtitle\Domain\Interfaces\SubtitleMovieSummaryResolverInterface;
use App\Subtitle\Domain\Interfaces\SubtitleReportRepositoryInterface;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;
use App\Subtitle\Domain\Interfaces\SubtitleUserSummaryResolverInterface;
use App\Subtitle\Infrastructure\Persistence\Repositories\EloquentSubtitleReportRepository;
use App\Subtitle\Infrastructure\Persistence\Repositories\EloquentSubtitleRepository;
use App\Subtitle\Infrastructure\Services\CatalogSubtitleMovieResolver;
use App\Subtitle\Infrastructure\Services\CatalogSubtitleMovieSummaryResolver;
use App\Subtitle\Infrastructure\Services\EloquentSubtitleUserSummaryResolver;
use App\Subtitle\Infrastructure\Services\LocalSubtitleFileStorage;
use App\Subtitle\Infrastructure\Services\OpenSubtitlesProvider;
use App\Subtitle\Infrastructure\Services\PlainTextSubtitleConverter;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserActiveSessionsFinderInterface;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserAuthenticationIssuerInterface;
use App\User\Domain\Interfaces\UserAuthenticationRefresherInterface;
use App\User\Domain\Interfaces\UserAuthenticationRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Repositories\EloquentUserActiveSessionsFinder;
use App\User\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\User\Infrastructure\Services\LaravelPasswordHasher;
use App\Wishlist\Application\SetMyPinnedFavorites\SetMyPinnedFavorites;
use App\Wishlist\Domain\Interfaces\MovieListItemResolverInterface;
use App\Wishlist\Domain\Interfaces\PinnedFavoriteRepositoryInterface;
use App\Wishlist\Domain\Interfaces\WatchLaterItemRepositoryInterface;
use App\Wishlist\Domain\Interfaces\WishlistItemRepositoryInterface;
use App\Wishlist\Infrastructure\Persistence\Repositories\EloquentPinnedFavoriteRepository;
use App\Wishlist\Infrastructure\Persistence\Repositories\EloquentWatchLaterItemRepository;
use App\Wishlist\Infrastructure\Persistence\Repositories\EloquentWishlistItemRepository;
use App\Wishlist\Infrastructure\Services\CatalogMovieListItemResolver;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(PasswordHasherInterface::class, LaravelPasswordHasher::class);
        $this->app->bind(RefreshTokenIssuerInterface::class, RandomRefreshTokenIssuer::class);
        $this->app->bind(RefreshTokenRepositoryInterface::class, EloquentRefreshTokenRepository::class);
        $this->app->bind(
            UserActiveSessionsFinderInterface::class,
            EloquentUserActiveSessionsFinder::class,
        );

        $this->app->scoped(AccessTokenIssuerInterface::class, function (): AccessTokenIssuerInterface {
            return new FirebaseJwtAccessTokenIssuer($this->jwtSecret());
        });

        $this->app->scoped(AccessTokenVerifierInterface::class, function (): AccessTokenVerifierInterface {
            return new FirebaseJwtAccessTokenVerifier($this->jwtSecret());
        });

        $this->app->scoped(UserAuthenticationIssuerInterface::class, function ($app): UserAuthenticationIssuerInterface {
            return new JwtUserAuthenticationIssuer(
                $app->make(AccessTokenIssuerInterface::class),
                $app->make(RefreshTokenIssuerInterface::class),
                $app->make(RefreshTokenRepositoryInterface::class),
                $app->make(TransactionRunnerInterface::class),
                $this->accessTtlSeconds(),
                $this->refreshTtlSeconds(),
                $this->maxConcurrentSessions(),
            );
        });

        $this->app->scoped(UserAuthenticationRefresherInterface::class, function ($app): UserAuthenticationRefresherInterface {
            return new JwtUserAuthenticationRefresher(
                $app->make(RefreshTokenRepositoryInterface::class),
                $app->make(UserRepositoryInterface::class),
                $app->make(AccessTokenIssuerInterface::class),
                $app->make(RefreshTokenIssuerInterface::class),
                $app->make(TransactionRunnerInterface::class),
                $this->accessTtlSeconds(),
                $this->refreshTtlSeconds(),
            );
        });

        $this->app->scoped(UserAuthenticationRevokerInterface::class, function ($app): UserAuthenticationRevokerInterface {
            return new JwtUserAuthenticationRevoker(
                $app->make(RefreshTokenRepositoryInterface::class),
                $app->make(UserRepositoryInterface::class),
            );
        });

        $this->app->scoped(
            UserAuthenticationGlobalRevokerInterface::class,
            JwtUserAuthenticationGlobalRevoker::class,
        );

        $this->app->scoped(UserIdResolverInterface::class, EloquentUserIdResolver::class);
        $this->app->scoped(MovieIdResolverInterface::class, EloquentMovieIdResolver::class);
        $this->app->scoped(PhysicalCopyIdResolverInterface::class, EloquentPhysicalCopyIdResolver::class);
        $this->app->scoped(AuthContextHolder::class);
        $this->app->bind(TransactionRunnerInterface::class, LaravelTransactionRunner::class);
        $this->app->bind(DomainEventDispatcherInterface::class, LaravelDomainEventDispatcher::class);
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
        $this->app->bind(NotificationDispatcherInterface::class, DatabaseNotificationDispatcher::class);

        $this->app->bind(MovieRepositoryInterface::class, EloquentMovieRepository::class);
        $this->app->bind(PersonRepositoryInterface::class, EloquentPersonRepository::class);
        $this->app->bind(GenreRepositoryInterface::class, EloquentGenreRepository::class);
        $this->app->bind(MovieCreditRepositoryInterface::class, EloquentMovieCreditRepository::class);
        $this->app->bind(BarcodeLookupRepositoryInterface::class, EloquentBarcodeLookupRepository::class);
        $this->app->bind(StreamableMovieFinderInterface::class, EloquentStreamableMovieFinder::class);
        $this->app->bind(PurchasableMovieFinderInterface::class, EloquentPurchasableMovieFinder::class);

        $this->app->bind(SystemCurrencyProviderInterface::class, ConfigSystemCurrencyProvider::class);
        $this->app->bind(PhysicalCopyRepositoryInterface::class, EloquentPhysicalCopyRepository::class);
        $this->app->bind(SaleProposalRepositoryInterface::class, EloquentSaleProposalRepository::class);
        $this->app->bind(PricingSettingsRepositoryInterface::class, EloquentPricingSettingsRepository::class);
        $this->app->bind(MovieTitleResolverInterface::class, CatalogMovieTitleResolver::class);
        $this->app->bind(InventoryMovieSummaryResolverInterface::class, InventoryCatalogMovieSummaryResolver::class);

        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(StripeWebhookEventStoreInterface::class, EloquentStripeWebhookEventStore::class);
        $this->app->bind(StockReservationInterface::class, InventoryStockReservation::class);
        $this->app->bind(CopyDetailsProviderInterface::class, InventoryCopyDetailsProvider::class);

        $this->app->scoped(CheckoutGatewayInterface::class, function (): CheckoutGatewayInterface {
            return new StripeCheckoutGateway($this->stripeSecret(), $this->stripeWebhookSecret());
        });

        $this->app->bind(CreateCheckoutSession::class, function ($app): CreateCheckoutSession {
            return new CreateCheckoutSession(
                $app->make(CartRepositoryInterface::class),
                $app->make(CopyDetailsProviderInterface::class),
                $app->make(OrderRepositoryInterface::class),
                $app->make(StockReservationInterface::class),
                $app->make(CheckoutGatewayInterface::class),
                $app->make(TransactionRunnerInterface::class),
                $this->checkoutTtlMinutes(),
                $this->frontendUrl(),
                $app->make(SystemCurrencyProviderInterface::class),
            );
        });

        $this->app->bind(WishlistItemRepositoryInterface::class, EloquentWishlistItemRepository::class);
        $this->app->bind(WatchLaterItemRepositoryInterface::class, EloquentWatchLaterItemRepository::class);
        $this->app->bind(PinnedFavoriteRepositoryInterface::class, EloquentPinnedFavoriteRepository::class);
        $this->app->bind(MovieListItemResolverInterface::class, CatalogMovieListItemResolver::class);

        $this->app->bind(ReviewRepositoryInterface::class, EloquentReviewRepository::class);
        $this->app->bind(ReviewLikeRepositoryInterface::class, EloquentReviewLikeRepository::class);
        $this->app->bind(ReviewReportRepositoryInterface::class, EloquentReviewReportRepository::class);
        $this->app->bind(UserDisplayResolverInterface::class, UserDisplayResolver::class);

        $this->app->bind(SubtitleRepositoryInterface::class, EloquentSubtitleRepository::class);
        $this->app->bind(SubtitleReportRepositoryInterface::class, EloquentSubtitleReportRepository::class);
        $this->app->bind(SubtitleConverterInterface::class, PlainTextSubtitleConverter::class);
        $this->app->bind(SubtitleMovieResolverInterface::class, CatalogSubtitleMovieResolver::class);
        $this->app->bind(SubtitleMovieSummaryResolverInterface::class, CatalogSubtitleMovieSummaryResolver::class);
        $this->app->bind(SubtitleUserSummaryResolverInterface::class, EloquentSubtitleUserSummaryResolver::class);

        $this->app->scoped(SubtitleFileStorageInterface::class, function (): SubtitleFileStorageInterface {
            return new LocalSubtitleFileStorage((string) config('subtitle.storage_path'));
        });

        $this->app->scoped(ExternalSubtitleProviderInterface::class, function ($app): ExternalSubtitleProviderInterface {
            return new OpenSubtitlesProvider(
                $app->make(HttpFactory::class),
                (string) config('services.opensubtitles.api_key'),
                (string) config('services.opensubtitles.base_url'),
                (string) config('services.opensubtitles.user_agent'),
                (string) config('services.opensubtitles.username'),
                (string) config('services.opensubtitles.password'),
            );
        });

        $this->app->bind(VideoFileRepositoryInterface::class, EloquentVideoFileRepository::class);
        $this->app->bind(PlaybackProgressRepositoryInterface::class, EloquentPlaybackProgressRepository::class);
        $this->app->bind(MovieResolverForStreamingInterface::class, CatalogMovieResolverForStreaming::class);
        $this->app->bind(MovieSummaryResolverInterface::class, CatalogMovieSummaryResolver::class);
        $this->app->bind(TranscodingJobDispatcherInterface::class, LaravelTranscodingJobDispatcher::class);

        $this->app->scoped(VideoFileStorageInterface::class, function (): VideoFileStorageInterface {
            return new LocalVideoFileStorage(
                (string) config('streaming.videos_path'),
                (string) config('streaming.originals_subdir'),
            );
        });

        $this->app->scoped(VideoTranscoderInterface::class, function (): VideoTranscoderInterface {
            return new FfmpegVideoTranscoder(
                (string) config('streaming.ffmpeg.binary'),
                (int) config('streaming.ffmpeg.threads'),
                (string) config('streaming.ffmpeg.preset'),
                (int) config('streaming.ffmpeg.crf'),
            );
        });

        $this->app->bind(TranscodeVideo::class, function ($app): TranscodeVideo {
            return new TranscodeVideo(
                $app->make(VideoFileRepositoryInterface::class),
                $app->make(VideoTranscoderInterface::class),
                $app->make(VideoFileStorageInterface::class),
                $app->make(TransactionRunnerInterface::class),
                $app->make(DomainEventDispatcherInterface::class),
                (bool) config('streaming.keep_original_after_processing'),
            );
        });

        $this->app->bind(CleanupFailedOriginals::class, function ($app): CleanupFailedOriginals {
            return new CleanupFailedOriginals(
                $app->make(VideoFileRepositoryInterface::class),
                $app->make(VideoFileStorageInterface::class),
                $this->streamingCleanupFailedAfterDays(),
            );
        });

        $this->app->bind(SetMyPinnedFavorites::class, function ($app): SetMyPinnedFavorites {
            return new SetMyPinnedFavorites(
                $app->make(PinnedFavoriteRepositoryInterface::class),
                $app->make(MovieListItemResolverInterface::class),
                $this->wishlistPinnedMaxSlots(),
            );
        });

        $this->app->scoped(TmdbClientInterface::class, function ($app): TmdbClientInterface {
            $httpClient = new LaravelHttpTmdbClient(
                $app->make(HttpFactory::class),
                $this->tmdbReadAccessToken(),
                (string) config('services.tmdb.base_url'),
                (string) config('services.tmdb.default_language'),
            );

            return new CachedTmdbClient(
                $httpClient,
                $app->make(CacheRepository::class),
                (int) config('services.tmdb.cache.search_ttl_seconds'),
                (int) config('services.tmdb.cache.detail_ttl_seconds'),
                (int) config('services.tmdb.cache.recommendations_ttl_seconds'),
                (int) config('services.tmdb.cache.person_ttl_seconds'),
            );
        });

        $this->app->scoped(BarcodeApiClientInterface::class, function ($app): BarcodeApiClientInterface {
            return new UpcDatabaseBarcodeApiClient(
                $app->make(HttpFactory::class),
                (string) config('services.barcode.api_key'),
                (string) config('services.barcode.base_url'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PhysicalCopyAdded::class, [RegisterBarcodeOnPhysicalCopyAdded::class, 'handle']);
        Event::listen(SaleProposalAccepted::class, [RegisterBarcodeOnSaleProposalAccepted::class, 'handle']);
        Event::listen(OrderReadyForPickup::class, [NotifyOrderReadyForPickup::class, 'handle']);
        Event::listen(SaleProposalResolved::class, [NotifySaleProposalResolved::class, 'handle']);
        Event::listen(ReviewLiked::class, [NotifyReviewLiked::class, 'handle']);
        Event::listen(ReviewRemovedByModeration::class, [NotifyReviewRemovedByModeration::class, 'handle']);
        Event::listen(SubtitleRemovedByModeration::class, [NotifySubtitleRemovedByModeration::class, 'handle']);
        Event::listen(PhysicalCopyAvailableForSale::class, [NotifyWishlistedUsersWhenMovieInStock::class, 'handle']);
        Event::listen(VideoFileReady::class, [NotifyWatchLaterUsersWhenMovieStreamable::class, 'handle']);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CancelExpiredOrdersCommand::class,
                CleanupFailedOriginalsCommand::class,
            ]);
        }
    }

    private function jwtSecret(): string
    {
        $secret = (string) config('auth_tokens.jwt_secret');

        if (trim($secret) === '') {
            throw new \InvalidArgumentException('AUTH_JWT_SECRET is required.');
        }

        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('AUTH_JWT_SECRET is invalid.');
        }

        return $secret;
    }

    private function accessTtlSeconds(): int
    {
        $seconds = (int) config('auth_tokens.access_ttl_seconds');

        if ($seconds <= 0) {
            throw new \InvalidArgumentException('AUTH_ACCESS_TTL_SECONDS must be greater than 0.');
        }

        return $seconds;
    }

    private function refreshTtlSeconds(): int
    {
        $accessTtlSeconds = $this->accessTtlSeconds();
        $seconds = (int) config('auth_tokens.refresh_ttl_seconds');

        if ($seconds <= 0) {
            throw new \InvalidArgumentException('AUTH_REFRESH_TTL_SECONDS must be greater than 0.');
        }

        if ($seconds <= $accessTtlSeconds) {
            throw new \InvalidArgumentException('AUTH_REFRESH_TTL_SECONDS must be greater than AUTH_ACCESS_TTL_SECONDS.');
        }

        return $seconds;
    }

    private function maxConcurrentSessions(): int
    {
        $max = (int) config('auth_tokens.max_concurrent_sessions');

        if ($max <= 0) {
            throw new \InvalidArgumentException('AUTH_MAX_CONCURRENT_SESSIONS must be greater than 0.');
        }

        return $max;
    }

    private function tmdbReadAccessToken(): string
    {
        $token = (string) config('services.tmdb.read_access_token');

        if (trim($token) === '') {
            throw new \InvalidArgumentException('TMDB_READ_ACCESS_TOKEN is required.');
        }

        return $token;
    }

    private function stripeSecret(): string
    {
        $secret = (string) config('services.stripe.secret');

        if (trim($secret) === '') {
            throw new \InvalidArgumentException('STRIPE_SECRET is required.');
        }

        return $secret;
    }

    private function stripeWebhookSecret(): string
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if (trim($secret) === '') {
            throw new \InvalidArgumentException('STRIPE_WEBHOOK_SECRET is required.');
        }

        return $secret;
    }

    private function frontendUrl(): string
    {
        $url = (string) config('order.frontend_url');

        if (trim($url) === '') {
            throw new \InvalidArgumentException('FRONTEND_URL is required.');
        }

        return $url;
    }

    private function checkoutTtlMinutes(): int
    {
        $minutes = (int) config('order.checkout_ttl_minutes');

        if ($minutes <= 0) {
            throw new \InvalidArgumentException('ORDER_CHECKOUT_TTL_MINUTES must be greater than 0.');
        }

        return $minutes;
    }

    private function wishlistPinnedMaxSlots(): int
    {
        $max = (int) config('wishlist.pinned_max_slots');

        if ($max <= 0) {
            throw new \InvalidArgumentException('WISHLIST_PINNED_MAX_SLOTS must be greater than 0.');
        }

        return $max;
    }

    private function streamingCleanupFailedAfterDays(): int
    {
        $days = (int) config('streaming.cleanup_failed_after_days');

        if ($days <= 0) {
            throw new \InvalidArgumentException('STREAMING_CLEANUP_FAILED_AFTER_DAYS must be greater than 0.');
        }

        return $days;
    }
}
