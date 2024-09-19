<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_origins' => [
        'https://yumekana.sakuraweb.com',
        'https://lala.sakuraweb.com',
        // 他の必要なサブドメイン
    ],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_headers' => [
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'Authorization',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN'
    ],
    'allowed_origins_patterns' => [
    'https:\/\/.*\.sakuraweb\.com$',
		],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => true,
];