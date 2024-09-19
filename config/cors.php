<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_methods' => ['*'],
  'allowed_origins' => [
      'https://gajumaro.sakura.ne.jp/yumekana',
      'https://gajumaro.sakura.ne.jp/yumekana-lala',
      'http://localhost:5174/yumekana',
  ],
  'allowed_headers' => ['*'],
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true,  // クッキーを送信する場合は true
];