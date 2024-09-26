<?php

use Illuminate\Support\Str;

return [
  'lifetime' => env('SESSION_LIFETIME', 120),
  'expire_on_close' => false,
  'encrypt' => false,
  'lottery' => [2, 100],
  'cookie' => env(
    'SESSION_COOKIE',
    Str::slug(env('APP_NAME', 'laravel'), '_') . '_session'
  ),
  'domain' => env('SESSION_DOMAIN', 'gajumaro.jp'),
  'path' => '/',
  'secure' => env('SESSION_SECURE_COOKIE', true),
  'http_only' => true,
  'same_site' => 'None',
];
