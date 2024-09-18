<?php

// config/cors.php
return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_origins' => ['https://yumekana.sakuraweb.com'],
  'allowed_methods' => ['*'],
  'allowed_headers' => ['*'],
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true,
];