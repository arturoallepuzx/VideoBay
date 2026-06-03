<?php

use App\Auth\Infrastructure\Http\Middleware\EnsureXsrfCookie;
use App\Auth\Infrastructure\Http\Middleware\VerifyCsrfTokenStateless;
use App\Catalog\Infrastructure\Entrypoint\Http\GetMovieDetailController as CatalogGetMovieDetailController;
use App\Catalog\Infrastructure\Entrypoint\Http\GetPersonDetailController as CatalogGetPersonDetailController;
use App\Catalog\Infrastructure\Entrypoint\Http\GetSimilarMoviesController as CatalogGetSimilarMoviesController;
use App\Catalog\Infrastructure\Entrypoint\Http\ListPersonFilmographyController as CatalogListPersonFilmographyController;
use App\Catalog\Infrastructure\Entrypoint\Http\ListPurchasableMoviesController as CatalogListPurchasableMoviesController;
use App\Catalog\Infrastructure\Entrypoint\Http\ListStreamableMoviesController as CatalogListStreamableMoviesController;
use App\Catalog\Infrastructure\Entrypoint\Http\ResolveBarcodeController as CatalogResolveBarcodeController;
use App\Catalog\Infrastructure\Entrypoint\Http\SearchMoviesController as CatalogSearchMoviesController;
use App\Catalog\Infrastructure\Entrypoint\Http\SearchPeopleController as CatalogSearchPeopleController;
use App\Inventory\Infrastructure\Entrypoint\Http\AddPhysicalCopyController as InventoryAddPhysicalCopyController;
use App\Inventory\Infrastructure\Entrypoint\Http\ConfirmStockSaleController as InventoryConfirmStockSaleController;
use App\Inventory\Infrastructure\Entrypoint\Http\EstimateCopyPriceController as InventoryEstimateCopyPriceController;
use App\Inventory\Infrastructure\Entrypoint\Http\GetCopyDetailController as InventoryGetCopyDetailController;
use App\Inventory\Infrastructure\Entrypoint\Http\GetPricingRulesController as InventoryGetPricingRulesController;
use App\Inventory\Infrastructure\Entrypoint\Http\ListAvailableCopiesController as InventoryListAvailableCopiesController;
use App\Inventory\Infrastructure\Entrypoint\Http\ProposeSaleToStoreController as InventoryProposeSaleToStoreController;
use App\Inventory\Infrastructure\Entrypoint\Http\ReleaseReservedStockController as InventoryReleaseReservedStockController;
use App\Inventory\Infrastructure\Entrypoint\Http\ReserveStockController as InventoryReserveStockController;
use App\Inventory\Infrastructure\Entrypoint\Http\ReviewSaleProposalController as InventoryReviewSaleProposalController;
use App\Inventory\Infrastructure\Entrypoint\Http\UpdatePhysicalCopyController as InventoryUpdatePhysicalCopyController;
use App\Inventory\Infrastructure\Entrypoint\Http\UpdatePricingRulesController as InventoryUpdatePricingRulesController;
use App\Notification\Infrastructure\Entrypoint\Http\ListMyNotificationsController as NotificationListMyNotificationsController;
use App\Notification\Infrastructure\Entrypoint\Http\MarkAllNotificationsAsReadController as NotificationMarkAllNotificationsAsReadController;
use App\Notification\Infrastructure\Entrypoint\Http\MarkNotificationAsReadController as NotificationMarkNotificationAsReadController;
use App\Order\Infrastructure\Entrypoint\Http\AddToCartController as OrderAddToCartController;
use App\Order\Infrastructure\Entrypoint\Http\CreateCheckoutSessionController as OrderCreateCheckoutSessionController;
use App\Order\Infrastructure\Entrypoint\Http\GetCartController as OrderGetCartController;
use App\Order\Infrastructure\Entrypoint\Http\GetOrderForPickupController as OrderGetOrderForPickupController;
use App\Order\Infrastructure\Entrypoint\Http\GetOrderStatusCountsController as OrderGetOrderStatusCountsController;
use App\Order\Infrastructure\Entrypoint\Http\ListAllOrdersController as OrderListAllOrdersController;
use App\Order\Infrastructure\Entrypoint\Http\ListMyOrdersController as OrderListMyOrdersController;
use App\Order\Infrastructure\Entrypoint\Http\MarkOrderAsPickedUpByCodeController as OrderMarkOrderAsPickedUpByCodeController;
use App\Order\Infrastructure\Entrypoint\Http\MarkOrderAsPickedUpController as OrderMarkOrderAsPickedUpController;
use App\Order\Infrastructure\Entrypoint\Http\RemoveFromCartController as OrderRemoveFromCartController;
use App\Order\Infrastructure\Entrypoint\Http\StripeWebhookController as OrderStripeWebhookController;
use App\Order\Infrastructure\Entrypoint\Http\UpdateCartItemQuantityController as OrderUpdateCartItemQuantityController;
use App\Review\Infrastructure\Entrypoint\Http\CreateReviewController as ReviewCreateReviewController;
use App\Review\Infrastructure\Entrypoint\Http\DeleteReviewController as ReviewDeleteReviewController;
use App\Review\Infrastructure\Entrypoint\Http\ListMovieReviewsController as ReviewListMovieReviewsController;
use App\Review\Infrastructure\Entrypoint\Http\ListPendingReviewReportsController as ReviewListPendingReviewReportsController;
use App\Review\Infrastructure\Entrypoint\Http\ListUserReviewsController as ReviewListUserReviewsController;
use App\Review\Infrastructure\Entrypoint\Http\ReportReviewController as ReviewReportReviewController;
use App\Review\Infrastructure\Entrypoint\Http\ResolveReviewReportController as ReviewResolveReviewReportController;
use App\Review\Infrastructure\Entrypoint\Http\ToggleReviewLikeController as ReviewToggleReviewLikeController;
use App\Review\Infrastructure\Entrypoint\Http\UpdateReviewController as ReviewUpdateReviewController;
use App\Streaming\Infrastructure\Entrypoint\Http\DeleteVideoFileController as StreamingDeleteVideoFileController;
use App\Streaming\Infrastructure\Entrypoint\Http\EnqueueTranscodingFromExistingController as StreamingEnqueueTranscodingFromExistingController;
use App\Streaming\Infrastructure\Entrypoint\Http\GetPlaybackProgressController as StreamingGetPlaybackProgressController;
use App\Streaming\Infrastructure\Entrypoint\Http\ListContinueWatchingController as StreamingListContinueWatchingController;
use App\Streaming\Infrastructure\Entrypoint\Http\ListMyWatchHistoryController as StreamingListMyWatchHistoryController;
use App\Streaming\Infrastructure\Entrypoint\Http\ListPendingVideoFilesController as StreamingListPendingVideoFilesController;
use App\Streaming\Infrastructure\Entrypoint\Http\ListVideoFilesForAdminController as StreamingListVideoFilesForAdminController;
use App\Streaming\Infrastructure\Entrypoint\Http\ReassignVideoFileController as StreamingReassignVideoFileController;
use App\Streaming\Infrastructure\Entrypoint\Http\RecordPlaybackProgressController as StreamingRecordPlaybackProgressController;
use App\Streaming\Infrastructure\Entrypoint\Http\RegisterExistingVideoFileController as StreamingRegisterExistingVideoFileController;
use App\Streaming\Infrastructure\Entrypoint\Http\ServeVideoRangeController as StreamingServeVideoRangeController;
use App\Streaming\Infrastructure\Entrypoint\Http\UploadVideoFileController as StreamingUploadVideoFileController;
use App\Subtitle\Infrastructure\Entrypoint\Http\ImportExternalSubtitleController as SubtitleImportExternalSubtitleController;
use App\Subtitle\Infrastructure\Entrypoint\Http\ListMovieSubtitlesController as SubtitleListMovieSubtitlesController;
use App\Subtitle\Infrastructure\Entrypoint\Http\ListPendingSubtitleReportsController as SubtitleListPendingSubtitleReportsController;
use App\Subtitle\Infrastructure\Entrypoint\Http\ReportSubtitleController as SubtitleReportSubtitleController;
use App\Subtitle\Infrastructure\Entrypoint\Http\ResolveSubtitleReportController as SubtitleResolveSubtitleReportController;
use App\Subtitle\Infrastructure\Entrypoint\Http\SearchExternalSubtitlesController as SubtitleSearchExternalSubtitlesController;
use App\Subtitle\Infrastructure\Entrypoint\Http\ServeSubtitleFileController as SubtitleServeSubtitleFileController;
use App\Subtitle\Infrastructure\Entrypoint\Http\UploadUserSubtitleController as SubtitleUploadUserSubtitleController;
use App\User\Infrastructure\Entrypoint\Http\ChangePasswordPostController as UserChangePasswordPostController;
use App\User\Infrastructure\Entrypoint\Http\DeleteController as UserDeleteController;
use App\User\Infrastructure\Entrypoint\Http\DeleteMyAccountController as UserDeleteMyAccountController;
use App\User\Infrastructure\Entrypoint\Http\ForceLogoutPostController as UserForceLogoutPostController;
use App\User\Infrastructure\Entrypoint\Http\GetAllController as UserGetAllController;
use App\User\Infrastructure\Entrypoint\Http\GetByIdController as UserGetByIdController;
use App\User\Infrastructure\Entrypoint\Http\GetMeController as UserGetMeController;
use App\User\Infrastructure\Entrypoint\Http\GetUsersWithActiveSessionsController as UserGetActiveSessionsController;
use App\User\Infrastructure\Entrypoint\Http\LoginPostController as UserLoginPostController;
use App\User\Infrastructure\Entrypoint\Http\LogoutAllPostController as UserLogoutAllPostController;
use App\User\Infrastructure\Entrypoint\Http\LogoutPostController as UserLogoutPostController;
use App\User\Infrastructure\Entrypoint\Http\PostController as UserPostController;
use App\User\Infrastructure\Entrypoint\Http\PutController as UserPutController;
use App\User\Infrastructure\Entrypoint\Http\RefreshPostController as UserRefreshPostController;
use App\User\Infrastructure\Entrypoint\Http\UpdateMyAccessibilitySettingsPutController as UserUpdateMyAccessibilitySettingsPutController;
use App\User\Infrastructure\Entrypoint\Http\UpdateMyProfilePutController as UserUpdateMyProfilePutController;
use App\Wishlist\Infrastructure\Entrypoint\Http\AddToWatchLaterController as WishlistAddToWatchLaterController;
use App\Wishlist\Infrastructure\Entrypoint\Http\AddToWishlistController as WishlistAddToWishlistController;
use App\Wishlist\Infrastructure\Entrypoint\Http\ListMyPinnedFavoritesController as WishlistListMyPinnedFavoritesController;
use App\Wishlist\Infrastructure\Entrypoint\Http\ListMyWatchLaterController as WishlistListMyWatchLaterController;
use App\Wishlist\Infrastructure\Entrypoint\Http\ListMyWishlistController as WishlistListMyWishlistController;
use App\Wishlist\Infrastructure\Entrypoint\Http\RemoveFromWatchLaterController as WishlistRemoveFromWatchLaterController;
use App\Wishlist\Infrastructure\Entrypoint\Http\RemoveFromWishlistController as WishlistRemoveFromWishlistController;
use App\Wishlist\Infrastructure\Entrypoint\Http\SetMyPinnedFavoritesController as WishlistSetMyPinnedFavoritesController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->group(function () {
    Route::post('/login', UserLoginPostController::class)
        ->middleware('throttle:10,1');
    Route::post('/refresh', UserRefreshPostController::class)
        ->middleware('throttle:30,1');
    Route::post('/logout', UserLogoutPostController::class)
        ->middleware('throttle:30,1');
    Route::post('/logout-all', UserLogoutAllPostController::class)
        ->middleware(['auth.access_token', 'throttle:30,1']);
    Route::get('/me', UserGetMeController::class)
        ->middleware('auth.access_token');
    Route::put('/me', UserUpdateMyProfilePutController::class)
        ->middleware(['auth.access_token', 'throttle:30,1']);
    Route::put('/me/accessibility-settings', UserUpdateMyAccessibilitySettingsPutController::class)
        ->middleware(['auth.access_token', 'throttle:30,1']);
    Route::delete('/me', UserDeleteMyAccountController::class)
        ->middleware(['auth.access_token', 'throttle:5,1']);
    Route::post('/me/password', UserChangePasswordPostController::class)
        ->middleware(['auth.access_token', 'throttle:10,1']);
});

Route::prefix('/users')
    ->middleware(['auth.access_token', 'auth.role:admin'])
    ->group(function () {
        Route::post('/', UserPostController::class);
        Route::get('/', UserGetAllController::class);
        Route::get('/active-sessions', UserGetActiveSessionsController::class);
        Route::get('/{userId}', UserGetByIdController::class)->whereUuid('userId');
        Route::put('/{userId}', UserPutController::class)->whereUuid('userId');
        Route::delete('/{userId}', UserDeleteController::class)->whereUuid('userId');
        Route::post('/{userId}/force-logout', UserForceLogoutPostController::class)
            ->whereUuid('userId')
            ->middleware('throttle:30,1');
    });

Route::prefix('/catalog')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/movies/search', CatalogSearchMoviesController::class)
            ->middleware('throttle:60,1');
        Route::get('/movies/streamable', CatalogListStreamableMoviesController::class);
        Route::get('/movies/purchasable', CatalogListPurchasableMoviesController::class);
        Route::get('/movies/{identifier}/similar', CatalogGetSimilarMoviesController::class)
            ->where('identifier', '[A-Za-z0-9-]+');
        Route::get('/movies/{identifier}', CatalogGetMovieDetailController::class)
            ->where('identifier', '[A-Za-z0-9-]+');

        Route::get('/people/search', CatalogSearchPeopleController::class)
            ->middleware('throttle:60,1');
        Route::get('/people/{identifier}/filmography', CatalogListPersonFilmographyController::class)
            ->where('identifier', '[A-Za-z0-9-]+');
        Route::get('/people/{identifier}', CatalogGetPersonDetailController::class)
            ->where('identifier', '[A-Za-z0-9-]+');

        Route::post('/barcode/resolve', CatalogResolveBarcodeController::class)
            ->middleware('throttle:30,1');
    });

Route::prefix('/inventory')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/copies', InventoryListAvailableCopiesController::class);
        Route::get('/copies/{copyId}', InventoryGetCopyDetailController::class)->whereUuid('copyId');
        Route::get('/estimate', InventoryEstimateCopyPriceController::class);
        Route::post('/proposals', InventoryProposeSaleToStoreController::class)
            ->middleware('throttle:30,1');

        Route::middleware('auth.role:admin')->group(function () {
            Route::post('/copies', InventoryAddPhysicalCopyController::class);
            Route::put('/copies/{copyId}', InventoryUpdatePhysicalCopyController::class)->whereUuid('copyId');
            Route::post('/copies/{copyId}/reserve', InventoryReserveStockController::class)->whereUuid('copyId');
            Route::post('/copies/{copyId}/release', InventoryReleaseReservedStockController::class)->whereUuid('copyId');
            Route::post('/copies/{copyId}/confirm', InventoryConfirmStockSaleController::class)->whereUuid('copyId');
            Route::post('/proposals/{proposalId}/review', InventoryReviewSaleProposalController::class)->whereUuid('proposalId');
            Route::get('/pricing', InventoryGetPricingRulesController::class);
            Route::put('/pricing', InventoryUpdatePricingRulesController::class);
        });
    });

Route::prefix('/cart')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/', OrderGetCartController::class);
        Route::post('/items', OrderAddToCartController::class);
        Route::put('/items/{copyId}', OrderUpdateCartItemQuantityController::class)->whereUuid('copyId');
        Route::delete('/items/{copyId}', OrderRemoveFromCartController::class)->whereUuid('copyId');
    });

Route::post('/checkout', OrderCreateCheckoutSessionController::class)
    ->middleware(['auth.access_token', 'throttle:10,1']);

Route::prefix('/orders')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/', OrderListMyOrdersController::class);
        Route::post('/{orderId}/pickup', OrderMarkOrderAsPickedUpController::class)
            ->whereUuid('orderId')
            ->middleware('auth.role:admin');
    });

Route::prefix('/admin/orders')
    ->middleware(['auth.access_token', 'auth.role:admin'])
    ->group(function () {
        Route::get('/', OrderListAllOrdersController::class);
        Route::get('/counts', OrderGetOrderStatusCountsController::class);
        Route::get('/pickup/{pickupCode}', OrderGetOrderForPickupController::class);
        Route::post('/pickup/{pickupCode}', OrderMarkOrderAsPickedUpByCodeController::class);
    });

Route::prefix('/wishlist')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/', WishlistListMyWishlistController::class);
        Route::post('/{movieId}', WishlistAddToWishlistController::class)->whereUuid('movieId');
        Route::delete('/{movieId}', WishlistRemoveFromWishlistController::class)->whereUuid('movieId');
    });

Route::prefix('/watch-later')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/', WishlistListMyWatchLaterController::class);
        Route::post('/{movieId}', WishlistAddToWatchLaterController::class)->whereUuid('movieId');
        Route::delete('/{movieId}', WishlistRemoveFromWatchLaterController::class)->whereUuid('movieId');
    });

Route::prefix('/favorites')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/', WishlistListMyPinnedFavoritesController::class);
        Route::put('/', WishlistSetMyPinnedFavoritesController::class);
    });

Route::prefix('/notifications')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/', NotificationListMyNotificationsController::class);
        Route::post('/read-all', NotificationMarkAllNotificationsAsReadController::class);
        Route::post('/{notificationId}/read', NotificationMarkNotificationAsReadController::class)->whereUuid('notificationId');
    });

Route::prefix('/movies/{movieId}/subtitles')
    ->middleware('auth.access_token')
    ->whereUuid('movieId')
    ->group(function () {
        Route::get('/', SubtitleListMovieSubtitlesController::class);
        Route::get('/external', SubtitleSearchExternalSubtitlesController::class)
            ->middleware('throttle:30,1');
        Route::post('/import', SubtitleImportExternalSubtitleController::class)
            ->middleware('throttle:10,1');
        Route::post('/upload', SubtitleUploadUserSubtitleController::class)
            ->middleware('throttle:10,1');
    });

Route::prefix('/subtitles')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/{subtitleId}', SubtitleServeSubtitleFileController::class)->whereUuid('subtitleId');
        Route::post('/{subtitleId}/report', SubtitleReportSubtitleController::class)
            ->whereUuid('subtitleId')
            ->middleware('throttle:30,1');
    });

Route::prefix('/admin/subtitle-reports')
    ->middleware(['auth.access_token', 'auth.role:admin'])
    ->group(function () {
        Route::get('/', SubtitleListPendingSubtitleReportsController::class);
        Route::post('/{reportId}/resolve', SubtitleResolveSubtitleReportController::class)->where('reportId', '\d+');
    });

Route::prefix('/movies/{movieId}/reviews')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/', ReviewListMovieReviewsController::class)->whereUuid('movieId');
        Route::post('/', ReviewCreateReviewController::class)->whereUuid('movieId');
    });

Route::get('/users/{userId}/reviews', ReviewListUserReviewsController::class)
    ->middleware('auth.access_token')
    ->whereUuid('userId');

Route::prefix('/reviews')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::put('/{reviewId}', ReviewUpdateReviewController::class)->whereUuid('reviewId');
        Route::delete('/{reviewId}', ReviewDeleteReviewController::class)->whereUuid('reviewId');
        Route::post('/{reviewId}/like', ReviewToggleReviewLikeController::class)->whereUuid('reviewId');
        Route::post('/{reviewId}/report', ReviewReportReviewController::class)->whereUuid('reviewId');
    });

Route::prefix('/admin/review-reports')
    ->middleware(['auth.access_token', 'auth.role:admin'])
    ->group(function () {
        Route::get('/', ReviewListPendingReviewReportsController::class);
        Route::post('/{reportId}/resolve', ReviewResolveReviewReportController::class)->where('reportId', '\d+');
    });

Route::prefix('/admin/videos')
    ->middleware(['auth.access_token', 'auth.role:admin'])
    ->group(function () {
        Route::get('/', StreamingListVideoFilesForAdminController::class);
        Route::get('/pending', StreamingListPendingVideoFilesController::class);
        Route::post('/upload', StreamingUploadVideoFileController::class);
        Route::post('/register', StreamingRegisterExistingVideoFileController::class);
        Route::post('/transcode-existing', StreamingEnqueueTranscodingFromExistingController::class);
        Route::put('/{videoFileId}/movie', StreamingReassignVideoFileController::class)->whereUuid('videoFileId');
        Route::delete('/{videoFileId}', StreamingDeleteVideoFileController::class)->whereUuid('videoFileId');
    });

Route::get('/stream/{videoFileId}', StreamingServeVideoRangeController::class)
    ->middleware('auth.access_token')
    ->whereUuid('videoFileId');

Route::prefix('/playback')
    ->middleware('auth.access_token')
    ->group(function () {
        Route::get('/continue-watching', StreamingListContinueWatchingController::class);
        Route::get('/history', StreamingListMyWatchHistoryController::class);
        Route::get('/{movieId}', StreamingGetPlaybackProgressController::class)->whereUuid('movieId');
        Route::put('/{movieId}', StreamingRecordPlaybackProgressController::class)->whereUuid('movieId');
    });

Route::post('/webhooks/stripe', OrderStripeWebhookController::class)
    ->withoutMiddleware([EnsureXsrfCookie::class, VerifyCsrfTokenStateless::class]);
