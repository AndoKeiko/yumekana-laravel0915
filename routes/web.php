<?php

use App\Http\Controllers\Auth\LoginController;
use Google\Rpc\Context\AttributeContext\Request;
// routes/web.phpに次のルートを追加します。
use Illuminate\Support\Facades\Route;

// firebase-messaging-sw.js を提供するルート
// Route::get('/firebase-messaging-sw.js', function () {
//   return response()->file(public_path('firebase-messaging-sw.js'), [
//       'Content-Type' => 'application/javascript; charset=UTF-8',
//   ]);
// });

// 既存のキャッチオールルート
Route::get('/{any}', function () {
  return view('app');
})->where('any', '.*');

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);