<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BypassAuthInTestEnvironment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (config('app.env') === 'local' && $request->header('X-Bypass-Auth') === 'test-environment') {
            Auth::loginUsingId(4); // テスト用ユーザーID
            Log::warning('警告: 認証がバイパスされています。これは課題提出用環境です。');
        }
        return $next($request);
    }
}
