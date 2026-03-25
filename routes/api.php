<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AccessKeyAuth;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/session', [SessionController::class, 'login']);

// Admin (JWT auth)
Route::middleware('auth:api')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
    Route::patch('/user', [UserController::class, 'update']);
    Route::put('/key', [UserController::class, 'regenerateKey']);
    Route::get('/stats', [StatsController::class, 'index']);
    Route::get('/download', [CommentController::class, 'download']);
    Route::get('/guests', [GuestController::class, 'index']);
});

// Guest (access key or JWT)
Route::middleware(AccessKeyAuth::class)->group(function () {
    Route::get('/v2/config', [ConfigController::class, 'show']);
    Route::get('/v2/comment', [CommentController::class, 'index']);
    Route::post('/comment', [CommentController::class, 'store']);
    Route::put('/comment/{own}', [CommentController::class, 'update']);
    Route::delete('/comment/{own}', [CommentController::class, 'destroy']);

    // Like/Unlike
    Route::post('/comment/{uuid}', [CommentController::class, 'like']);
    Route::patch('/comment/{uuid}', [CommentController::class, 'unlike']);
});
