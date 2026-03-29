<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ConferenceController;
use App\Http\Controllers\UserController;


// ── Public Routes ─────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ── Protected Routes ──────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // داخل auth:sanctum middleware:
    Route::prefix('journals')->group(function () {
    Route::get('/',                              [JournalController::class, 'index']);
    Route::post('/',                             [JournalController::class, 'store']);
    Route::get('/my',                            [JournalController::class, 'myJournals']);
    Route::get('/reviewing',                     [JournalController::class, 'reviewerJournals']);
    Route::get('/{journal}',                     [JournalController::class, 'show']);
    Route::post('/{journal}/assign-reviewers',   [JournalController::class, 'assignReviewers']);
    Route::post('/{journal}/review',             [JournalController::class, 'submitReview']);
});

Route::prefix('conferences')->group(function () {
    Route::get('/',                        [ConferenceController::class, 'index']);
    Route::post('/',                       [ConferenceController::class, 'store']);
    Route::get('/my',                      [ConferenceController::class, 'myConferences']);
    Route::get('/{conference}',            [ConferenceController::class, 'show']);
    Route::post('/{conference}/register',  [ConferenceController::class, 'register']);
    Route::post('/{conference}/unregister',[ConferenceController::class, 'unregister']);
    Route::post('/{conference}/certificate/{userId}',
    [ConferenceController::class, 'issueCertificate']);
});

// داخل auth:sanctum:
Route::get('/researchers',         [UserController::class, 'researchers']);
Route::get('/users/{user}/profile',[UserController::class, 'profile']);

    // Auth
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/me',               [AuthController::class, 'me']);
    Route::put('/profile',          [AuthController::class, 'updateProfile']);

    // Posts
    Route::get('/posts',            [PostController::class, 'index']);
    Route::post('/posts',           [PostController::class, 'store']);
    Route::get('/posts/{post}',     [PostController::class, 'show']);
    Route::delete('/posts/{post}',  [PostController::class, 'destroy']);

    // Comments & Likes
    Route::post('/posts/{post}/comments', [PostController::class, 'addComment']);
    Route::post('/posts/{post}/like',     [PostController::class, 'toggleLike']);

    // Messages
    Route::get('/messages', [MessageController::class, 'inbox']);
    Route::get('/messages/{user}',   [MessageController::class, 'conversation']);
    Route::post('/messages',         [MessageController::class, 'send']);

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/stats',                [AdminController::class, 'stats']);
        Route::patch('/users/{user}/role',  [AdminController::class, 'toggleRole']);
        Route::get('/users',              [AdminController::class, 'users']);
        Route::get('/posts',              [AdminController::class, 'posts']);
        Route::delete('/users/{user}',    [AdminController::class, 'deleteUser']);
        Route::delete('/posts/{post}',    [AdminController::class, 'deletePost']);
    });
});