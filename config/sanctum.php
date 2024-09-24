<?php

use Laravel\Sanctum\Sanctum;

return [
  'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
      '%s%s',
      'localhost:5174,gajumaro.sakura.ne.jp,www.gajumaro.sakura.ne.jp,::1',
      env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
  ))),
  'guard' => ['web'],
  'expiration' => null,
  'middleware' => [
      'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
      'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
  ],
  'prefix' => 'sanctum',
  'domain' => env('SESSION_DOMAIN', 'gajumaro.sakura.ne.jp'),
];