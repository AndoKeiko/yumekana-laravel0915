<?php

use Laravel\Sanctum\Sanctum;

return [
  'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost:5174,gajumaro.sakura.ne.jp')),
  'guard' => ['web', 'api'],
  // 'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 60 * 24),
  'expiration' => null,
  'middleware' => [
    'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
  ],
  'prefix' => 'sanctum',
  // 'domain' => env('SESSION_DOMAIN', 'gajumaro.sakura.ne.jp'),
  'domain' => env('SESSION_DOMAIN', 'gajumaro.sakura.ne.jp'),
];
