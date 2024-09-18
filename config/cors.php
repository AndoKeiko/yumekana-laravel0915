<?php

// config/cors.php
return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_methods' => ['*'],
  'allowed_origins' => ['https://yumekana.sakuraweb.com'], // フロントエンドのURL
  'allowed_headers' => ['*'],
  'exposed_headers' => ['XSRF-TOKEN'],
  'supports_credentials' => true,
];
