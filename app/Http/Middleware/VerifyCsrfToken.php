<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
  /**
   * The URIs that should be excluded from CSRF verification.
   *
   * @var array<int, string>
   */
  protected $except = [
    'api/*',  // APIルートをCSRF保護から除外
    'login',
    'register',
    'sanctum/csrf-cookie',  // SanctumのCSRFトークン取得ルートを除外
    'https://gajumaro.sakura.ne.jp/yumekana/*',
    'https://gajumaro.sakura.ne.jp/yumekana-lala/*',
  ];

  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   *
   * @throws \Illuminate\Session\TokenMismatchException
   */
  public function handle($request, Closure $next)
  {
      if (app()->environment('local', 'testing')) {
          Log::info('VerifyCsrfToken middleware reached', [
              'url' => $request->fullUrl(),
              'method' => $request->method(),
              'has_csrf_token' => $request->hasCookie('XSRF-TOKEN'),
              'has_csrf_header' => $request->header('X-XSRF-TOKEN')
          ]);
      }
  
      try {
          if (
              $this->isReading($request) ||
              $this->runningUnitTests() ||
              $this->inExceptArray($request) ||
              $this->tokensMatch($request)
          ) {
              return $this->addCookieToResponse($request, $next($request));
          }
      } catch (TokenMismatchException $e) {
          Log::warning('CSRF token mismatch', ['url' => $request->fullUrl()]);
          return response()->json(['error' => 'CSRF token mismatch'], 419);
      }
  
      throw new TokenMismatchException('CSRF token mismatch.');
  }
}