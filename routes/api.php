<?php

use App\Http\Controllers\AuditionContactController;
use App\Http\Controllers\AuditionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FixrWebhooksController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\SiteConfigController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SnippetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VolunteerController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Auth Routes
 */
Route::post('/login', [AuthController::class, 'login']);

// Protected route (requires auth)
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'getUser']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

/**
 * Fixr Webhook Handler
 */
Route::post('/fixr-webhooks', [FixrWebhooksController::class, 'create']);

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

/**
 * Fixr Icon
 */
Route::get('/storage/fixr-icon', function () {
    $path = storage_path("app/public/fixr.png");
    return Response::file($path);
});

/**
 * Image-related Routes
 */
Route::get('/storage/images/{filename}', function ($filename): BinaryFileResponse {
    $path = storage_path("app/public/images/{$filename}");

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

/**
 * Site Config
 */
Route::get('/site-config', [SiteConfigController::class, 'show']);
Route::middleware('auth:sanctum')->post('/update-site-config', [SiteConfigController::class, 'store']);

/**
 * User Routes
 */
Route::middleware('auth:sanctum')->get('/get-users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->post('/update-user/{id}', [UserController::class, 'update']);
Route::middleware('auth:sanctum')->post('/create-user', [UserController::class, 'store']);
Route::middleware('auth:sanctum')->post('/delete-user/{id}', [UserController::class, 'destroy']);

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

/**
 * Contact Routes
 */
Route::post('/create-contact', [ContactController::class, 'create']);

/**
 * Audition Routes
 */
Route::get('/current-audition', [AuditionController::class, 'show']);
Route::post('/audition-contact', [AuditionContactController::class, 'create']);

/**
 * Volunteer Routes
 */
Route::get('/skills', [SkillController::class, 'list']);
Route::post('/volunteer-contact', [VolunteerController::class, 'create']);
