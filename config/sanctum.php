<?php

use Laravel\Sanctum\Sanctum;

return [
  'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'gajumaro.sakura.ne.jp')),
  'guard' => ['web'],
  'middleware' => [
      'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
      'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
  ],
  'expiration' => 60 * 24, // 24時間
  'prefix' => 'sanctum',
];
