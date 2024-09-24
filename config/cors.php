<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_methods' => ['*'],
  'allowed_origins' => ['https://gajumaro.sakura.ne.jp'],  // フロントエンドのURLを許可
  'allowed_headers' => ['*'],
  'supports_credentials' => true,  // クッキーを共有するために必要
];