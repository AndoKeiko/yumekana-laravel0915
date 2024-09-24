<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'], // APIエンドポイントとCSRFクッキーのためのパス
  'allowed_methods' => ['*'], // 必要なHTTPメソッドを指定
  'allowed_origins' => ['https://gajumaro.sakura.ne.jp'], // フロントエンドのURLを指定
  'allowed_origins_patterns' => [],
  'allowed_headers' => ['*'], // 必要に応じて指定
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true, // 認証情報（Cookieなど）を送信するために必須
];