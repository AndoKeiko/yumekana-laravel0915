<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\GoalsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\FCMController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

// CORSプリフライトリクエスト用のルート（必要な場合）
// Route::options('/{any}', function () {
//   return response('', 204)
//       ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
//       ->header('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');
// })->where('any', '.*');

// 認証不要のルート
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/refresh', [LoginController::class, 'refresh']);
// Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
// Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//   Log::info('User request', [
//     'user' => $request->user(),
//     'headers' => $request->headers->all(),
//     'session' => $request->session()->all(),
//   ]);
//   return $request->user();
// });
// Sanctum認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
  Route::post('/logout', [LoginController::class, 'logout']);
  Route::get('/user', function (Request $request) {
    Log::info('User request', [
        'user' => $request->user() ? $request->user()->toArray() : null,
        'headers' => $request->headers->all(),
        'session' => $request->session()->all(),
        'cookies' => $request->cookies->all(),
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'auth' => [
            'check' => Auth::check(),
            'id' => Auth::id(),
        ],
    ]);

    if (!$request->user()) {
        Log::warning('Unauthenticated user request', [
            'headers' => $request->headers->all(),
            'session' => $request->session()->all(),
        ]);
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    return $request->user();
});
  Route::get('/user/me', [UsersController::class, 'me']); // この行を追加
  Route::post('/refresh-token', [LoginController::class, 'refresh']);
  Route::post('/update-fcm-token', [UsersController::class, 'updateFcmToken']);
  Route::post('/fcm-token', [FCMController::class, 'storeToken']);

  // Users関連のルート
  Route::prefix('users')->group(function () {
    Route::get('/', [UsersController::class, 'index']);
    Route::post('/', [UsersController::class, 'createOrGetUser']);
    Route::post('/complete-profile', [UsersController::class, 'completeProfile']);
  });

  // Goals関連のルート
  Route::prefix('goals')->group(function () {
    Route::post('/', [GoalsController::class, 'store']);
    Route::get('/', [GoalsController::class, 'index']);
    Route::get('/user/{userId}', [GoalsController::class, 'getUserGoals']);
    Route::get('/{goalId}', [GoalsController::class, 'show']);
    Route::delete('/{goalId}', [GoalsController::class, 'destroy']);
    Route::post('/{id}/chat', [GoalsController::class, 'chat']);
    Route::get('/{goalId}/chat-history', [GoalsController::class, 'getChatHistory']);

    // Tasks関連のルート
    Route::prefix('{goalId}/tasks')->group(function () {
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
});


Route::get('/debug', function (Request $request) {
  try {
      $user = $request->user();
      return response()->json([
          'user' => $user ? $user->toArray() : null,
          'authenticated' => Auth::check(),
          'session' => $request->session()->all(),
          'token' => $request->bearerToken(),
          'headers' => $request->headers->all(),
      ]);
  } catch (\Exception $e) {
      \Log::error('Debug route error:', [
          'message' => $e->getMessage(),
          'trace' => $e->getTraceAsString()
      ]);
      return response()->json([
          'error' => $e->getMessage(),
          'trace' => $e->getTraceAsString()
      ], 500);
  }
});