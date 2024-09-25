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
      // デバッグ用のログ出力（リクエストURL、メソッド、CSRFトークンの有無を記録）
      Log::info('VerifyCsrfToken middleware triggered', [
          'url' => $request->fullUrl(),
          'method' => $request->method(),
          'has_csrf_cookie' => $request->hasCookie('XSRF-TOKEN'),
          'csrf_token_in_header' => $request->header('X-XSRF-TOKEN'),
          'csrf_token_in_body' => $request->input('_token')
      ]);

      try {
          if (
              $this->isReading($request) ||
              $this->runningUnitTests() ||
              $this->inExceptArray($request) ||
              $this->tokensMatch($request)
          ) {
              // トークンが一致した場合、成功ログ
              Log::info('CSRF token matched for request', [
                  'url' => $request->fullUrl(),
                  'method' => $request->method(),
              ]);
              return $this->addCookieToResponse($request, $next($request));
          }
      } catch (TokenMismatchException $e) {
          // トークン不一致時のログ
          Log::warning('CSRF token mismatch', [
              'url' => $request->fullUrl(),
              'method' => $request->method(),
              'csrf_token_in_header' => $request->header('X-XSRF-TOKEN'),
              'csrf_token_in_body' => $request->input('_token'),
          ]);
          return response()->json(['error' => 'CSRF token mismatch'], 419);
      }

      // 例外処理：トークンが一致しなかった場合
      Log::error('Unhandled CSRF token mismatch', [
          'url' => $request->fullUrl(),
          'method' => $request->method(),
      ]);

      throw new TokenMismatchException('CSRF token mismatch.');
  }
}
