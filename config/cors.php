<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_origins' => ['https://gajumaro.jp'],
  'allowed_methods' => ['*'],
  'allowed_headers' => ['*'],
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true,
];