<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

  'name' => env('APP_NAME', 'Laravel'),

  'env' => env('APP_ENV', 'production'),

'debug' => env('APP_DEBUG', true),

  'url' => env('APP_URL', 'https://gajumaro.jp/yumekana-lala'),

  'asset_url' => env('ASSET_URL'),

  'timezone' => 'Asia/Tokyo',

  'locale' => 'ja',

  'fallback_locale' => 'ja',

  'faker_locale' => 'ja_JP',

  'key' => env('APP_KEY'),

  'cipher' => 'AES-256-CBC',

  'maintenance' => [
    'driver' => 'file',
    // 'store' => 'redis',
  ],

  'providers' => ServiceProvider::defaultProviders()->merge([
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    // App\Providers\BroadcastServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
  ])->toArray(),

  'aliases' => Facade::defaultAliases()->merge([
    // 'Example' => App\Facades\Example::class,
  ])->toArray(),

];
