<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\GoalsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\FCMController;

// 認証不要のルート
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::get('/sanctum/csrf-cookie', [LoginController::class, 'getCsrfCookie']);

// Google認証
Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Sanctum認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::post('/refresh', [LoginController::class, 'refresh']);
    
    // ユーザー関連
    Route::get('/user', [UsersController::class, 'getAuthUser']);
    Route::get('/user/me', [UsersController::class, 'getCurrentUser']);
    Route::post('/users', [UsersController::class, 'store']);
    Route::put('/users/{userId}', [UsersController::class, 'update']);
    
    // FCMトークン関連
    Route::post('/update-fcm-token', [FCMController::class, 'updateToken']);
    Route::delete('/delete-fcm-token', [FCMController::class, 'deleteToken']);

    // 目標関連
    Route::apiResource('goals', GoalsController::class);
    Route::get('/goals/user/{userId}', [GoalsController::class, 'getUserGoals']);
    Route::post('/goals/{goalId}/chat', [GoalsController::class, 'chat']);
    Route::get('/goals/{goalId}/chat-history', [GoalsController::class, 'getChatHistory']);

    // タスク関連
    Route::prefix('goals/{goalId}/tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::post('/save', [TaskController::class, 'saveTask']);
        Route::put('/order', [TaskController::class, 'updateOrder']);
        Route::put('/{taskId}', [TaskController::class, 'update']);
        Route::delete('/{taskId}', [TaskController::class, 'destroy']);
        Route::put('/{taskId}/elapsed-time', [TaskController::class, 'updateElapsedTime']);
        Route::put('/{taskId}/review-interval', [TaskController::class, 'updateReviewInterval']);
    });
});

// パスワードリセット（認証不要）
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

// デバッグルート（開発環境のみ）
if (app()->environment('local', 'staging')) {
    Route::get('/debug', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'authenticated' => Auth::check(),
            'session' => $request->session()->all(),
        ]);
    });
}