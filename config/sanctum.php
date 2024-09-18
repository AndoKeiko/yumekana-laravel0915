<?php

use Laravel\Sanctum\Sanctum;

return [
  'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'yumekana.sakuraweb.com,localhost,localhost:8000,127.0.0.1,127.0.0.1:8000,::1')),
  'guard' => ['web'],
  'expiration' => null,
  'middleware' => [
      'verify_csrf_token' => \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
      'encrypt_cookies' => \App\Http\Middleware\EncryptCookies::class,
      'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
  ],
];