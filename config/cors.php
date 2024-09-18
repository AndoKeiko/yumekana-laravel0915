<?php

return [
  'supports_credentials' => true,
  'allowed_origins' => ['https://yumekana.sakuraweb.com'],  // フロントエンドのURL
  'allowed_methods' => ['*'],
  'allowed_headers' => ['*'],
  'exposed_headers' => ['XSRF-TOKEN'],
  'max_age' => 0,
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
];
