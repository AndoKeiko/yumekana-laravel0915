<?php

use Laravel\Sanctum\Sanctum;

return [
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'gajumaro.sakura.ne.jp')),
  'prefix' => '',
  'guard' => ['api'],
  'expiration' => null,
  'middleware' => [
      'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
      'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
  ],
  'domain' => env('SESSION_DOMAIN', 'gajumaro.sakura.ne.jp'),
];