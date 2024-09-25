<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],  // SanctumのCSRF用パスを追加
  'allowed_methods' => ['*'],
  'allowed_origins' => ['https://gajumaro.sakura.ne.jp'],  // フロントエンドのURLを指定
  'allowed_origins_patterns' => [],
  'allowed_headers' => ['*'],
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true, // クッキーを利用するため、trueに設定
];