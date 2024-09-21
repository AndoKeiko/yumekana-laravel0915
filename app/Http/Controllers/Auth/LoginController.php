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
      // 'email' と 'password' が必須で、emailは正しい形式である必要があります。
      $request->validate([
        'email' => 'required|email',
        'password' => 'required',
      ]);

      // ユーザーをメールアドレスで検索
      $user = \App\Models\User::where('email', $request->email)->first();

      // ユーザーが見つからないか、パスワードが一致しない場合はエラーを返す
      if (!$user || !Hash::check($request->password, $user->password)) {
        // ログイン失敗をログに記録
        Log::warning('Login failed: Invalid credentials', ['email' => $request->email]);

        // バリデーションエラーを投げる（フロントエンド側で処理される）
        throw ValidationException::withMessages([
          'email' => ['The provided credentials are incorrect.'],
        ]);
      }

      // ログイン成功をログに記録
      Log::info('User authenticated successfully', ['user_id' => $user->id]);

      // アクセストークンを作成
      $token = $user->createToken('auth-token')->plainTextToken;
      // トークン作成成功をログに記録
      Log::info('Token created successfully');

      // リフレッシュトークンを作成（Sanctumのデフォルトではこれを自動で返しません）
      $refreshToken = $user->createToken('refresh-token', ['*'], now()->addDays(7))->plainTextToken;

      // フロントエンドにアクセストークンとユーザー情報を返す
      return response()->json([
        'access_token' => $token,
        'refresh_token' => $refreshToken,
        'token_type' => 'Bearer', // Bearerトークンのタイプ
        'user' => $user, // ユーザー情報も一緒に返す
      ]);
    } catch (\Exception $e) {
      // エラーが発生した場合はログに詳細な情報を記録
      Log::error('Login error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e; // フロントエンドに例外を返す
    }
  }

  public function logout(Request $request)
  {
    // ログアウト試行をログに記録
    Log::info('Logout attempt', ['user_id' => $request->user()->id]);

    try {
      // 現在のアクセストークンを削除してログアウト
      $request->user()->currentAccessToken()->delete();
      Log::info('User logged out successfully', ['user_id' => $request->user()->id]);

      // ログアウト成功メッセージを返す
      return response()->json(['message' => 'Logged out successfully']);
    } catch (\Exception $e) {
      // エラーが発生した場合の処理
      Log::error('Logout error', [
        'user_id' => $request->user()->id,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
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
          // リクエストからリフレッシュトークンを取得
          $refreshToken = $request->bearerToken();
  
          // リフレッシュトークンが存在しない場合
          if (!$refreshToken) {
              return response()->json(['error' => 'No refresh token provided'], 401);
          }
  
          // リフレッシュトークンをデータベースから取得
          $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::findToken($refreshToken);
  
          // トークンが存在しない、または無効な場合
          if (!$tokenRecord || !$tokenRecord->tokenable || $tokenRecord->expires_at->isPast()) {
              return response()->json(['error' => 'Invalid or expired refresh token'], 401);
          }
  
          // ユーザー情報を取得
          $user = $tokenRecord->tokenable;
  
          if (!$user) {
              return response()->json(['error' => 'User not found'], 401);
          }
  
          // 古いトークンを無効化
          $user->tokens()->delete();
  
          // 新しいアクセストークンとリフレッシュトークンを発行
          $newAccessToken = $user->createToken('auth-token', ['*'], now()->addMinutes(15));
          $newRefreshToken = $user->createToken('refresh-token', ['*'], now()->addDays(7));
  
          return response()->json([
              'access_token' => $newAccessToken->plainTextToken,
              'refresh_token' => $newRefreshToken->plainTextToken,
              'token_type' => 'bearer',
              'expires_in' => 900 // 15分
          ]);
      } catch (\Exception $e) {
          Log::error('Token refresh error', [
              'message' => $e->getMessage(),
              'trace' => $e->getTraceAsString()
          ]);
          return response()->json(['error' => 'Failed to refresh token'], 500);
      }
  }
  
}
