<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_methods' => ['*'],
  'allowed_origins' => ['https://gajumaro.sakura.ne.jp'],
  'allowed_headers' => ['*'],
  'supports_credentials' => true,
];
