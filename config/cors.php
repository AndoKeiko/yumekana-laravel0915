<?php

// return [
//     'paths' => ['api/*', 'sanctum/csrf-cookie'],
//     'allowed_origins' => [
//         'https://yumekana.sakuraweb.com',
//         'https://lala.sakuraweb.com',
//         // 他の必要なサブドメイン
//     ],
//     'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
//     'allowed_headers' => [
//         'X-Requested-With',
//         'Content-Type',
//         'Accept',
//         'Authorization',
//         'X-CSRF-TOKEN',
//         'X-XSRF-TOKEN'
//     ],
//     'allowed_origins_patterns' => [
//     'https:\/\/.*\.sakuraweb\.com$',
// 		],
//     'exposed_headers' => [],
//     'max_age' => 3600,
//     'supports_credentials' => true,
// ];


return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://yumekana.sakuraweb.com',
        'https://lala.sakuraweb.com',
        // 他の必要なサブドメイン
    ],
  'allowed_origins_patterns' => '#^https://.*\.sakuraweb\.com$#',
  'allowed_headers' => ['*'],
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true,  // クッキーやHTTP認証を使用する場合はtrueに設定
];