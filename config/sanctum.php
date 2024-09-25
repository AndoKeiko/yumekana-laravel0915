<?php

use Laravel\Sanctum\Sanctum;

return [
  'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'gajumaro.sakura.ne.jp')),
  'guard' => ['web'],
  // 'middleware' => [
  //   'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
  //   'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
  // ],
  'domain' => env('SANCTUM_COOKIE_DOMAIN', 'gajumaro.sakura.ne.jp'),
  'expiration' => 60 * 24, // 24時間
  'refresh_ttl' => 20160, // 2週間
  'same_site' => 'lax',
  'prefix' => 'sanctum',
];
