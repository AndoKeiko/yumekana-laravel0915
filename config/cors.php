<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_methods' => ['*'],
  'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5174,https://gajumaro.sakura.ne.jp')),
  'allowed_headers' => ['*'],
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true,  // クッキーを送信する場合は true
];
