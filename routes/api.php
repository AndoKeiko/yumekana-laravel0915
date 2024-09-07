<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\GoalsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\AuthController;


Route::options('/{any}', function() {
  return response('', 200)
      ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
      ->header('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');
})->where('any', '.*');

// 認証不要のルート
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/refresh', [LoginController::class, 'refresh'])->middleware('auth:sanctum');
Route::post('register', [RegisterController::class, 'register']);
// Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
// Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Sanctum認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [UsersController::class, 'index']);

    // Users関連のルート
    Route::prefix('users')->group(function () {
        Route::get('/', [UsersController::class, 'index']);
        Route::post('/', [UsersController::class, 'createOrGetUser']);
        Route::post('/complete-profile', [UsersController::class, 'completeProfile']);
    });

    // Goals関連のルート
    Route::prefix('goals')->group(function () {
        Route::post('/', [GoalsController::class, 'store'])->middleware('auth:sanctum');
        Route::get('/', [GoalsController::class, 'index']);
        Route::get('/user/{userId}', [GoalsController::class, 'getUserGoals']);
        Route::get('/{goalId}', [GoalsController::class, 'show']);
        Route::delete('/{goalId}', [GoalsController::class, 'destroy']);
        Route::post('/{id}/chat', [GoalsController::class, 'chat'])->middleware('auth:sanctum');
        Route::get('/{goalId}/chat-history', [GoalsController::class, 'getChatHistory']);

        // Tasks関連のルート
        Route::prefix('{goalId}/tasks')->group(function () {
            Route::get('/', [TaskController::class, 'index']);
            Route::post('/', [TaskController::class, 'store']);
            Route::post('/save', [TaskController::class, 'saveTask']);
            Route::put('/order', [TaskController::class, 'updateOrder']);
        });
    });
});