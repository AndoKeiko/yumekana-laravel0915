<?php

return [

  'paths' => ['api/*', 'sanctum/csrf-cookie'],

  'allowed_methods' => ['*'],

  'allowed_origins' => ['https://yumekana.sakuraweb.com', 'http://localhost:5174'],

  'allowed_origins_patterns' => [],

  'allowed_headers' => ['*'],

  'exposed_headers' => false,

  'max_age' => 0,

  'supports_credentials' => true,

];
