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

        // APIリクエストでない場合、loginルートにリダイレクト
        return route('login');
    }
}
