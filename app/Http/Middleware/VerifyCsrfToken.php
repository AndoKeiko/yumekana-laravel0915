<?php

// namespace App\Http\Middleware;

// use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

// class VerifyCsrfToken extends Middleware
// {
//     /**
//      * The URIs that should be excluded from CSRF verification.
//      *
//      * @var array<int, string>
//      */
//     protected $except = [
//        'api/*',  // APIルートをCSRF保護から除外
//        'login',
//        'register',
//     ];
// }


namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Closure;
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
    // CSRFトークンがクッキーに存在するか、ヘッダーに存在するかを確認するログ
    Log::info('VerifyCsrfToken middleware reached', [
      'url' => $request->fullUrl(),
      'method' => $request->method(),
      'has_csrf_token' => $request->hasCookie('XSRF-TOKEN'),
      'has_csrf_header' => $request->header('X-XSRF-TOKEN')
    ]);

    if (
      $this->isReading($request) ||
      $this->runningUnitTests() ||
      $this->inExceptArray($request) ||
      $this->tokensMatch($request)
    ) {

      Log::info('CSRF check passed or not required');
      return $this->addCookieToResponse($request, $next($request));
    }

    Log::warning('CSRF token mismatch');
    throw new TokenMismatchException('CSRF token mismatch.');
  }
}
