<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_methods' => ['*'],
  'allowed_origins' => ['https://gajumaro.sakura.ne.jp', 'http://localhost:5174'],
  'allowed_headers' => ['*'],
  'supports_credentials' => true,
];
