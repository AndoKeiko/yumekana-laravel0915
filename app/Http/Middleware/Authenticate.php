<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // APIリクエスト（JSONを期待するリクエスト）の場合はリダイレクトを行わない
        if ($request->expectsJson()) {
            return null;
        }

        // React側のルートを使うため、リダイレクトしない
        // ここでのリダイレクトを取り除き、APIでは401エラーを返す
        return null;  // これにより、リダイレクトなし
    }
}
