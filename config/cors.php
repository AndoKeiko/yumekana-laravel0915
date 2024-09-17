<?php

return [

  'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register'],
  'allowed_methods' => ['*'],
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5174,http://127.0.0.1:5174','https://gajumaro.sakura.ne.jp/yumekana/')),
  'allowed_origins_patterns' => [],
  'allowed_headers' => ['*'],
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true,
];
