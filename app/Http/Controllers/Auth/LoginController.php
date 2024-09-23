<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{

  public function login(Request $request)
  {
    // ログイン試行をログに記録
    Log::info('Login attempt', ['email' => $request->email]);

    try {
      // 入力のバリデーション
      $request->validate([
        'email' => 'required|email',
        'password' => 'required',
      ]);

      // ユーザーをメールアドレスで検索
      $user = \App\Models\User::where('email', $request->email)->first();

      // ユーザーが見つからないか、パスワードが一致しない場合はエラーを返す
      if (!$user || !Hash::check($request->password, $user->password)) {
        Log::warning('Login failed: Invalid credentials', ['email' => $request->email]);
        throw ValidationException::withMessages([
          'email' => ['The provided credentials are incorrect.'],
        ]);
      }

      Log::info('User authenticated successfully', ['user_id' => $user->id]);

      // アクセストークンとリフレッシュトークンを作成
      $accessToken = $user->createToken('auth-token', ['*'])->plainTextToken;
      $refreshToken = $user->createToken('refresh-token', ['*'])->plainTextToken;

      // クッキーにトークンを設定
      $accessTokenCookie = cookie('access_token', $accessToken, 15, null, null, false, true);
      $refreshTokenCookie = cookie('refresh_token', $refreshToken, 10080, null, null, false, true);

      Log::info('Tokens created successfully', [
        'user_id' => $user->id,
        'access_token' => substr($accessToken, 0, 10),
        'refresh_token' => substr($refreshToken, 0, 10)
      ]);

      return response()->json([
        'user' => $user,
        'message' => 'Login successful',
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken
      ])->cookie($accessTokenCookie)
        ->cookie($refreshTokenCookie);
    } catch (\Exception $e) {
      Log::error('Login error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      return response()->json(['error' => 'Login failed', 'message' => $e->getMessage()], 401);
    }
  }


  public function logout(Request $request)
  {
    // 現在のアクセストークンを削除
    $request->user()->currentAccessToken()->delete();

    // クッキーを無効化
    $accessTokenCookie = cookie('access_token', '', -1, null, null, false, true);
    $refreshTokenCookie = cookie('refresh_token', '', -1, null, null, false, true);

    return response()->json(['message' => 'Logged out successfully'])
      ->cookie($accessTokenCookie)
      ->cookie($refreshTokenCookie);
  }


  public function user(Request $request)
  {
    // ユーザー情報取得リクエストをログに記録
    Log::info('User info request', ['user_id' => $request->user()->id]);

    try {
      // 認証されたユーザー情報を返す
      return response()->json($request->user());
    } catch (\Exception $e) {
      // エラーが発生した場合
      Log::error('Error fetching user info', [
        'user_id' => $request->user()->id,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
  }

  public function refresh(Request $request)
  {
    try {
      $refreshToken = $request->cookie('refresh_token');

      if (!$refreshToken) {
        Log::warning('No refresh token provided');
        return response()->json(['error' => 'No refresh token provided'], 401);
      }

      // リフレッシュトークンをデータベースから取得
      $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::findToken($refreshToken);

      if (
        !$tokenRecord ||
        !$tokenRecord->tokenable ||
        ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast())
      ) {
        Log::warning('Invalid or expired refresh token', [
          'refresh_token' => $refreshToken
        ]);
        return response()->json(['error' => 'Invalid or expired refresh token'], 401);
      }

      $user = $tokenRecord->tokenable;

      // 古いトークンを削除
      $user->tokens()->delete();

      // 新しいアクセストークンとリフレッシュトークンを作成
      $newAccessToken = $user->createToken('auth-token', ['*']);
      $newAccessToken->token->expires_at = now()->addMinutes(15);
      $newAccessToken->token->save();

      $newRefreshToken = $user->createToken('refresh-token', ['*']);
      $newRefreshToken->token->expires_at = now()->addDays(7);
      $newRefreshToken->token->save();

      // クッキーに新しいトークンを設定
      $accessTokenCookie = cookie('access_token', $newAccessToken->plainTextToken, 15, null, null, false, true);
      $refreshTokenCookie = cookie('refresh_token', $newRefreshToken->plainTextToken, 10080, null, null, false, true);

      return response()->json([
        'message' => 'Token refreshed successfully'
      ])->cookie($accessTokenCookie)->cookie($refreshTokenCookie);
    } catch (\Exception $e) {
      Log::error('Token refresh error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      return response()->json(['error' => 'Failed to refresh token'], 500);
    }
  }
}
