<?php

use App\Http\Controllers\AngelController;
use App\Http\Controllers\AngelLevelController;
use App\Http\Controllers\AnnouncementBannerController;
use App\Http\Controllers\AuditionContactController;
use App\Http\Controllers\AuditionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FixrWebhooksController;
use App\Http\Controllers\FlexPurchaseController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\SiteConfigController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SnippetController;
use App\Http\Controllers\StandardButtonsController;
use App\Http\Controllers\SupportUsController;
use App\Http\Controllers\TicketSaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VolunteerController;
use App\Models\PermissionLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
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
 * Announcement Banner
 */
Route::get('/announcement-banner', [AnnouncementBannerController::class, 'show']);
Route::put('/announcement-banner', [AnnouncementBannerController::class, 'update'])
    ->middleware(('auth:sanctum'));

/**
 * Show-related Routes
 */
Route::get('all-shows', [ShowController::class, 'index']);
Route::get('season-shows', [ShowController::class, 'seasonShows']);
Route::get('home-shows', [ShowController::class, 'homeShows']);

// Protected routes (requires auth)
Route::middleware('auth:sanctum')->post('/create-show', [ShowController::class, 'create']);
Route::middleware('auth:sanctum')->post('/update-show', [ShowController::class, 'update']);
Route::middleware('auth:sanctum')->get('show/{id}', [ShowController::class, 'show']);
Route::middleware('auth:sanctum')->post('delete-show/{id}', [ShowController::class, 'destroy']);
Route::middleware('auth:sanctum')->get('new-show-template', [ShowController::class, 'newShow']);
Route::middleware('auth:sanctum')->put('update-tentative/{id}', [ShowController::class, 'updateTentative']);

/**
 * Fixr Icon
 */
Route::get('/storage/fixr-icon', function () {
    $path = storage_path("app/public/fixr.png");
    return Response::file($path);
});

/**
 * Flex Image
 */
Route::get('/storage/flex-image', function () {
    $path = storage_path("app/public/flex-image.jpg");
    return Response::file($path);
});

/**
 * Image-related Routes
 */
Route::get('/storage/{path}/{filename}', function ($path, $filename): BinaryFileResponse {
    $path = storage_path("app/public/{$path}/{$filename}");

    if (! file_exists($path)) {
        $path = storage_path('app/private/image-not-found.jpeg');
    }

    return Response::file($path);
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
Route::middleware('auth:sanctum')->post('/update-image', [ImageController::class, 'update']);

/**
 * Performance-related Routes
 */
Route::middleware('auth:sanctum')->post('/upsert-performances', [PerformanceController::class, 'upsert']);

Route::middleware('auth:sanctum')->post('/update-fixr-link', [PerformanceController::class, 'updateFixrLink']);

/**
 * Site Config
 */
Route::get('/site-config', [SiteConfigController::class, 'show']);
Route::middleware('auth:sanctum')->post('/update-site-config', [SiteConfigController::class, 'store']);

/**
 * User Routes
 */
Route::middleware('auth:sanctum')->get('/get-users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->put('/users/{id}', [UserController::class, 'update']);
Route::middleware('auth:sanctum')->put('/change-password/{id}', [UserController::class, 'changePassword']);
Route::middleware('auth:sanctum')->post('/create-user', [UserController::class, 'store']);
Route::middleware('auth:sanctum')->post('/delete-user/{id}', [UserController::class, 'destroy']);
Route::middleware('auth:sanctum')->get('/users/{id}', [UserController::class, 'show']);
Route::middleware('auth:sanctum')->get('/permission-levels', function () {
    return PermissionLevel::orderBy('label')->get();
});

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
 * Gallery Routes
 */
Route::get('/gallery', [GalleryController::class, 'index']);
Route::post('/gallery', [GalleryController::class, 'store'])
    ->middleware('auth:sanctum');
Route::delete('/gallery/{id}', [GalleryController::class, 'delete'])
    ->middleware('auth:sanctum');
Route::put('/gallery', [GalleryController::class, 'update'])
    ->middleware('auth:sanctum');


/**
 * Contact Routes
 */
Route::post('/create-contact', [ContactController::class, 'store']);

/**
 * Audition Routes
 */
Route::get('/current-audition', [AuditionController::class, 'current']);
Route::get('/audition/{id}', [AuditionController::class, 'show'])
    ->middleware('auth:sanctum');
Route::post('/audition', [AuditionController::class, 'store'])
    ->middleware('auth:sanctum');
Route::put('/audition/{id}', [AuditionController::class, 'update'])
    ->middleware('auth:sanctum');
Route::post('/audition-contact', [AuditionContactController::class, 'create']);

/**
 * Volunteer Routes
 */
Route::get('/skills', [SkillController::class, 'list']);
Route::post('/volunteer-contact', [VolunteerController::class, 'contactCreate']);
Route::get('/volunteers', [VolunteerController::class, 'index']);
Route::delete('/volunteers/{id}', [VolunteerController::class, 'destroy'])
    ->middleware('auth:sanctum');
Route::post('/volunteers', [VolunteerController::class, 'store'])
    ->middleware('auth:sanctum');
Route::put('/volunteers/{id}', [VolunteerController::class, 'update'])
    ->middleware('auth:sanctum');

/**
 * Flex Purchase
 */
Route::get('/flex-purchase-config', [FlexPurchaseController::class, 'show']);
Route::put('/flex-purchase-config', [FlexPurchaseController::class, 'update'])
    ->middleware('auth:sanctum');
Route::post('/flex-purchase-config/image', [FlexPurchaseController::class, 'image'])
    ->middleware('auth:sanctum');

/**
 * Contact Routes
 */
Route::get('/contacts', [ContactController::class, 'index'])
    ->middleware('auth:sanctum');
Route::delete('/contacts/{id}', [ContactController::class, 'destroy'])
    ->middleware('auth:sanctum');

/**
 * Standard Button Routes
 */
Route::get('/standard-buttons/{amount}', [StandardButtonsController::class, 'index']);

/**
 * Angel Routes - Add these to your existing api.php
 */

// Public routes
Route::get('/angels', [AngelLevelController::class, 'index']);

// Protected routes (requires auth)
Route::middleware('auth:sanctum')->group(function () {
    // Angel Levels
    Route::post('/angel-levels', [AngelLevelController::class, 'store']);
    Route::put('/angel-levels/{id}', [AngelLevelController::class, 'update']);
    Route::delete('/angel-levels/{id}', [AngelLevelController::class, 'destroy']);

    // Angels
    Route::post('/angels', [AngelController::class, 'store']);
    Route::put('/angels/{id}', [AngelController::class, 'update']);
    Route::delete('/angels/{id}', [AngelController::class, 'destroy']);
});

Route::get('/support-us', [SupportUsController::class, 'index']);

Route::controller(TicketSaleController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/ticket-sales', 'index');
    });
