<?php

use App\Http\Controllers\AngelController;
use App\Http\Controllers\AngelLevelController;
use App\Http\Controllers\AnnouncementBannerController;
use App\Http\Controllers\AuditionContactController;
use App\Http\Controllers\AuditionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangeLogController;
use App\Http\Controllers\CompTixController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FixrWebhooksController;
use App\Http\Controllers\FlexLinkController;
use App\Http\Controllers\FlexPurchaseController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\MessageUsController;
use App\Http\Controllers\PatronController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\SiteConfigController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SnippetController;
use App\Http\Controllers\SoldOutNotificationRecipientController;
use App\Http\Controllers\StandardButtonsController;
use App\Http\Controllers\SupportUsController;
use App\Http\Controllers\TicketSaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VolunteerController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Auth Routes
 */
Route::get('/login', function () {
    return response()->json(['status' => 'need-sign-in']);
})->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('sign-in');
Route::get('/refresh-permissions', [AuthController::class, 'refreshPermissions'])
    ->middleware('auth:sanctum')
    ->name('refresh-permissions');

// Protected route (requires auth)
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'getUser']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

/**
 * Fixr Webhook Handler
 */
Route::post('/fixr-webhooks', [FixrWebhooksController::class, 'create']);

/**
 * TEMPORARY: capture the raw postMessage data Fixr's checkout widget sends
 * on purchase completion, to see what shape it actually is. Remove once
 * that's confirmed.
 */
Route::post('/fixr-investigate', function (Request $request) {
    $filename = 'logs/fixr-investigate-' . date('Y_m_d_H_i_s') . '.txt';
    Storage::put($filename, $request->getContent());

    return response()->noContent();
});

/**
 * Announcement Banner
 */
Route::get('/announcement-banner', [AnnouncementBannerController::class, 'show']);
Route::put('/announcement-banner', [AnnouncementBannerController::class, 'update'])
    ->middleware(['auth:sanctum', 'permission:announcement-banner']);

/**
 * Show-related Routes
 */
Route::middleware(['auth:sanctum', 'permission:shows'])->get('all-shows', [ShowController::class, 'index']);
Route::get('shows/{id}', [ShowController::class, 'show']);
Route::get('shows/slug/{slug}', [ShowController::class, 'bySlug']);
Route::get('season-shows', [ShowController::class, 'seasonShows']);
Route::get('home-shows', [ShowController::class, 'homeShows']);
Route::get('shows/flex/{uid}', [FlexLinkController::class, 'showByUid']);
Route::middleware(['auth:sanctum', 'permission:shows'])->get('admin/flex-link/{showId}', [FlexLinkController::class, 'getOrCreate']);

// Protected routes (requires auth)
Route::middleware(['auth:sanctum', 'permission:shows'])->post('/create-show', [ShowController::class, 'create']);
Route::middleware(['auth:sanctum', 'permission:shows'])->post('/update-show', [ShowController::class, 'update']);
Route::middleware(['auth:sanctum', 'permission:shows'])->get('show/{id}', [ShowController::class, 'show']);
Route::middleware(['auth:sanctum', 'permission:shows'])->delete('shows/{id}', [ShowController::class, 'destroy']);
Route::middleware(['auth:sanctum', 'permission:shows'])->get('new-show-template', [ShowController::class, 'newShow']);
Route::middleware(['auth:sanctum', 'permission:shows'])->put('update-tentative/{id}', [ShowController::class, 'updateTentative']);

/**
 * Serves a storage file with long-lived cache headers (etag derived from
 * mtime+size, so it changes whenever the file is overwritten/re-optimized).
 */
$cacheableFile = function (string $path): BinaryFileResponse {
    return Response::file($path)->setCache([
        'public'        => true,
        'max_age'       => 604800,
        'etag'          => md5(filemtime($path) . filesize($path)),
        'last_modified' => new \DateTime('@' . filemtime($path)),
    ]);
};

/**
 * Fixr Icon
 */
Route::get('/storage/fixr-icon', function () use ($cacheableFile) {
    return $cacheableFile(storage_path("app/public/fixr.png"));
});

/**
 * Flex Image
 */
Route::get('/storage/flex-image', function () use ($cacheableFile) {
    return $cacheableFile(storage_path("app/public/flex-image.jpg"));
});

/**
 * Image-related Routes
 */
Route::get('/storage/{path}/{filename}', function ($path, $filename) use ($cacheableFile): BinaryFileResponse {
    $path = storage_path("app/public/{$path}/{$filename}");

    if (! file_exists($path)) {
        $path = storage_path('app/private/logo.png');
    }

    return $cacheableFile($path);
});

Route::get('/storage/sides/{filename}', function ($filename): BinaryFileResponse | JsonResponse {
    $path = storage_path("app/public/sides/{$filename}");

    if (! file_exists($path)) {
        return response()->json([
            'error' => 'Invalid file request',
        ]);
    }

    return response()->download($path);
});

// Protected route (requires auth)
Route::middleware(['auth:sanctum', 'permission:shows'])->post('/update-image', [ImageController::class, 'update']);

/**
 * Performance-related Routes
 */
Route::middleware(['auth:sanctum', 'permission:shows'])->post('/upsert-performances', [PerformanceController::class, 'upsert']);

Route::middleware(['auth:sanctum', 'permission:shows'])->post('/performances/sold-out-notifications', [PerformanceController::class, 'sendSoldOutNotifications']);

/**
 * Sold Out Notification Recipients
 */
Route::middleware(['auth:sanctum', 'permission:sold-out-notifications'])->get('/sold-out-notification-recipients', [SoldOutNotificationRecipientController::class, 'index']);
Route::middleware(['auth:sanctum', 'permission:sold-out-notifications'])->post('/sold-out-notification-recipients', [SoldOutNotificationRecipientController::class, 'store']);
Route::middleware(['auth:sanctum', 'permission:sold-out-notifications'])->put('/sold-out-notification-recipients/{id}', [SoldOutNotificationRecipientController::class, 'update']);
Route::middleware(['auth:sanctum', 'permission:sold-out-notifications'])->delete('/sold-out-notification-recipients/{id}', [SoldOutNotificationRecipientController::class, 'destroy']);

/**
 * Site Config
 */
Route::get('/site-config', [SiteConfigController::class, 'show']);
Route::middleware(['auth:sanctum', 'permission:site-config'])->put('/site-config', [SiteConfigController::class, 'update']);
Route::middleware(['auth:sanctum', 'permission:site-config'])->put('/site-config/standard-buttons', [SiteConfigController::class, 'updateButtons']);
Route::middleware(['auth:sanctum', 'permission:site-config'])->put('/site-config/support', [SiteConfigController::class, 'updateSupport']);
Route::middleware(['auth:sanctum', 'permission:site-config'])->put('/site-config/flex', [SiteConfigController::class, 'updateFlex']);
Route::middleware(['auth:sanctum', 'permission:site-config'])->put('/site-config/season', [SiteConfigController::class, 'updateSeason']);
Route::middleware(['auth:sanctum', 'permission:site-config'])->put('/site-config/angels', [SiteConfigController::class, 'updateAngels']);

/**
 * User Routes
 */
Route::middleware('auth:sanctum')->get('/get-users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->put('/users/{id}', [UserController::class, 'update']);
Route::middleware('auth:sanctum')->put('/change-password/{id}', [UserController::class, 'changePassword']);
Route::middleware('auth:sanctum')->post('/create-user', [UserController::class, 'store']);
Route::middleware('auth:sanctum')->delete('/users/{id}', [UserController::class, 'destroy']);
Route::middleware('auth:sanctum')->put('/users/{id}/permissions', [UserController::class, 'updatePermissions']);

/**
 * Change Log Routes
 */
Route::middleware(['auth:sanctum', 'permission:change-log'])->get('/change-logs', [ChangeLogController::class, 'index']);

/**
 * Snippet Routes
 */
Route::get('/get-snippet/{slug}', [SnippetController::class, 'show']);

/**
 * Course Routes
 */
Route::get('/open-courses', [CourseController::class, 'openEnrollment']);
Route::get('/course-details/{slug}', [CourseController::class, 'courseDetails']);
Route::post('/course-contact', [CourseController::class, 'courseContact']);

/**
 * Protected Course Routes
 */
Route::middleware(['auth:sanctum', 'permission:classes'])
    ->get('/admin/courses', [CourseController::class, 'index']);
Route::middleware(['auth:sanctum', 'permission:classes'])
    ->get('admin/courses/{id}', [CourseController::class, 'show']);
Route::middleware(['auth:sanctum', 'permission:classes'])
    ->post('/admin/courses/poster/{id}', [CourseController::class, 'uploadPoster']);
Route::middleware(['auth:sanctum', 'permission:classes'])
    ->post('/admin/courses/instructor/{id}', [CourseController::class, 'uploadInstructorPhoto']);
Route::middleware(['auth:sanctum', 'permission:classes'])
    ->post('/admin/courses', [CourseController::class, 'store']);
Route::middleware(['auth:sanctum', 'permission:classes'])
    ->put('/admin/courses/{id}', [CourseController::class, 'update']);
Route::middleware(['auth:sanctum', 'permission:classes'])
    ->delete('/admin/courses/{id}', [CourseController::class, 'destroy']);

/**
 * Gallery Routes
 */
Route::get('/gallery', [GalleryController::class, 'index']);
Route::post('/gallery', [GalleryController::class, 'store'])
    ->middleware(['auth:sanctum', 'permission:shows']);
Route::delete('/gallery/{id}', [GalleryController::class, 'delete'])
    ->middleware(['auth:sanctum', 'permission:shows']);
Route::put('/gallery', [GalleryController::class, 'update'])
    ->middleware(['auth:sanctum', 'permission:shows']);


/**
 * Contact Routes
 */
Route::post('/create-contact', [ContactController::class, 'store']);
Route::post('/message-us', [MessageUsController::class, 'store']);

/**
 * Audition Routes
 */
Route::get('/current-audition', [AuditionController::class, 'current']);
Route::get('/audition/{id}', [AuditionController::class, 'show'])
    ->middleware(['auth:sanctum', 'permission:shows']);
Route::post('/audition', [AuditionController::class, 'store'])
    ->middleware(['auth:sanctum', 'permission:shows']);
Route::put('/audition/{id}', [AuditionController::class, 'update'])
    ->middleware(['auth:sanctum', 'permission:shows']);
Route::post('/audition-contact', [AuditionContactController::class, 'create']);

/**
 * Volunteer Routes
 *
 * Admin CRUD for volunteers (index/store/update/destroy) was removed —
 * unused, no live admin page (AdminVolunteers.vue/AdminEditVolunteer.vue
 * were already commented out of the router). Only the public
 * contact-form submission stays. Re-add when that admin section actually
 * gets built.
 */
Route::get('/skills', [SkillController::class, 'list']);
Route::post('/volunteer-contact', [VolunteerController::class, 'contactCreate']);

/**
 * Flex Purchase
 */
Route::get('/flex-purchase-config', [FlexPurchaseController::class, 'show']);
Route::put('/flex-purchase-config', [FlexPurchaseController::class, 'update'])
    ->middleware(['auth:sanctum', 'permission:flex-purchase-config']);
Route::post('/flex-purchase-config/image', [FlexPurchaseController::class, 'image'])
    ->middleware(['auth:sanctum', 'permission:flex-purchase-config']);
Route::post('/flex-purchase', [FlexPurchaseController::class, 'store']);

/**
 * Contact Routes
 */
Route::get('/contacts', [ContactController::class, 'index'])
    ->middleware(['auth:sanctum', 'permission:contacts']);
Route::delete('/contacts/{id}', [ContactController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'permission:contacts']);

Route::get('/message-us', [MessageUsController::class, 'index'])
    ->middleware(['auth:sanctum', 'permission:quick-messages']);
Route::delete('/message-us/{id}', [MessageUsController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'permission:quick-messages']);

/**
 * Standard Button Routes
 */
Route::middleware(['auth:sanctum', 'permission:site-config'])->get('/standard-buttons', [StandardButtonsController::class, 'index']);

/**
 * Angel Routes - Add these to your existing api.php
 */

// Public routes
Route::get('/angels', [AngelLevelController::class, 'index']);
Route::post('/angel-donation', [AngelController::class, 'donate']);

// Protected routes (requires auth)
Route::middleware(['auth:sanctum', 'permission:our-angels'])->group(function () {
    // Angel Levels
    Route::post('/angel-levels', [AngelLevelController::class, 'store']);
    Route::put('/angel-levels/{id}', [AngelLevelController::class, 'update']);
    Route::delete('/angel-levels/{id}', [AngelLevelController::class, 'destroy']);

    // Angels
    Route::post('/angels', [AngelController::class, 'store']);
    Route::put('/angels/{id}', [AngelController::class, 'update']);
    Route::delete('/angels/{id}', [AngelController::class, 'destroy']);
    Route::get('/angels/seasons', [AngelController::class, 'seasons']);
    Route::get('/angels/by-season/{season}', [AngelController::class, 'bySeason']);
});

Route::get('/support-us', [SupportUsController::class, 'index']);

/**
 * Ticket Sale Routes
 */
Route::controller(TicketSaleController::class)
    ->middleware(['auth:sanctum', 'permission:ticket-sales'])
    ->group(function () {
        Route::get('/ticket-sales', 'index');
        Route::put('/ticket-sales', 'update');
        Route::delete('/ticket-sales', 'destroy');
        Route::put('/ticket-sales/no-show/{id}', 'updateNoShow');
        Route::post('/admin/ticket-sales', 'store');
    });

/**
 * Patron Routes
 */
Route::get('/patrons/lookup', [PatronController::class, 'lookup']);
Route::middleware(['auth:sanctum', 'permission:flex-purchases'])->get('/admin/flex-purchases', [PatronController::class, 'flexPurchases']);
Route::middleware(['auth:sanctum', 'permission:patrons'])->group(function () {
    Route::get('/admin/patrons', [PatronController::class, 'index']);
    Route::get('/admin/patrons/flex-history/{id}', [PatronController::class, 'flexHistory']);
});

// Public self-service purchase (PayPal/transfer/flex forms). Admin manual
// entry uses the auth-gated /admin/ticket-sales route above instead.
Route::controller(TicketSaleController::class)
    ->group(function () {
        Route::post('/ticket-sales', 'store');
    });

/**
 * Comp Routes
 */
Route::middleware(['auth:sanctum', 'permission:shows'])->group(function () {
    Route::get('/comp/{id}', [CompTixController::class, 'index']);

    Route::post('/comp', [CompTixController::class, 'store']);

    Route::delete('/comp/{uid}', [CompTixController::class, 'destroy']);

    Route::post('/comp/send/{id}', [CompTixController::class, 'send']);
});

Route::get('/comp/redeem/{uid}', [CompTixController::class, 'show']);

Route::post('/comp/redeem/{uid}', [CompTixController::class, 'update']);

/**
 * Payment Method Routes
 */

Route::middleware(['auth:sanctum', 'permission:payment-methods'])->resource('payment-methods', PaymentMethodController::class);
