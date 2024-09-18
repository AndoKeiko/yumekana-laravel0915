<?php

use Laravel\Sanctum\Sanctum;

return [

  'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'yumekana.sakuraweb.com,lala.sakuraweb.com')),

  'guard' => ['web'],

  'expiration' => null,


  'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

  'middleware' => [
    'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
    'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
  ],

];
