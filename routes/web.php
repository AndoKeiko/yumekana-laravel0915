<?php

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