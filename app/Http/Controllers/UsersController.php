<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * UsersController: ユーザー関連の操作（取得、作成、プロフィール完成）を扱うコントローラー
 */
class UsersController extends Controller
{
  /**
   * データベースから全ユーザーを取得する
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function index(Request $request): JsonResponse
  {
    Log::info('User info requested', ['user_id' => $request->user() ? $request->user()->id : 'guest']);

    $users = User::all();
    Log::info('Users retrieved', ['count' => $users->count()]);

    return response()->json([
      'message' => 'Users retrieved successfully',
      'users' => $users,
    ], 200);
  }


  public function createOrGetUser(Request $request): JsonResponse
  {
    // 1. バリデーションルールを定義
    $rules = [
      'firebase_uid' => 'required|string|max:255',
      'email' => 'required|email|max:255',
      'userName' => 'nullable|string|max:255',
      'is_google_user' => 'boolean',
    ];

    // 2. バリデータインスタンスを作成
    $validator = Validator::make($request->all(), $rules);

    // 3. バリデーションチェック
    if ($validator->fails()) {
      return response()->json([
        'message' => 'The given data was invalid.',
        'errors' => $validator->errors(),
      ], 422);
    }

    // 4. バリデーション済みデータを取得
    $validatedData = $validator->validated();

    try {
      $user = User::firstOrNew(['firebase_uid' => $validatedData['firebase_uid']]);

      if (!$user->exists) {
        $user->fill($validatedData);
        $user->is_google_user = $validatedData['is_google_user'] ?? false;
        $user->registration_completed = !($validatedData['is_google_user'] ?? false);
        $user->save();

        Log::info('New user created', ['user_id' => $user->id]);
        $message = 'User created successfully';
        $statusCode = 201;
      } else {
        Log::info('Existing user retrieved', ['user_id' => $user->id]);
        $message = 'User already exists';
        $statusCode = 200;
      }

      return response()->json([
        'message' => $message,
        'user' => $user,
        'requires_profile_completion' => !$user->is_profile_completed,
      ], $statusCode);
    } catch (\Exception $e) {
      Log::error('Error in createOrGetUser', ['error' => $e->getMessage()]);
      return response()->json([
        'message' => 'Failed to create or get user',
        'error' => $e->getMessage(),
      ], 500);
    }
  }


  /**
   * メールアドレスとユーザー名を追加してユーザープロフィールを完成させる
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function updateProfile(Request $request): JsonResponse
  {
    $user = $request->user();
    $validator = Validator::make($request->all(), [
      'name' => 'sometimes|required|string|max:255',
      'nickname' => 'nullable|string|max:255',
      'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
      'password' => 'nullable|string|min:8',
      'avatar' => 'nullable|string|max:255',
      'email_verified_at' => 'nullable|date',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'message' => 'The given data was invalid.',
        'errors' => $validator->errors(),
      ], 422);
    }

    try {
      $validatedData = $validator->validated();

      DB::transaction(function () use ($user, $validatedData) {
        if (isset($validatedData['password'])) {
          $validatedData['password'] = Hash::make($validatedData['password']);
        }

        $user->fill($validatedData);
        $user->is_profile_completed = true;
        $user->save();
      });

      Log::info('User profile updated', ['user_id' => $user->id]);
      return response()->json([
        'message' => 'User profile updated successfully',
        'user' => $user->fresh(),
      ], 200);
    } catch (\Exception $e) {
      Log::error('User profile update failed', ['error' => $e->getMessage()]);
      return response()->json([
        'message' => 'Failed to update user profile',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
  public function user(Request $request): JsonResponse
  {
    $user = $request->user();

    if (!$user) {
      return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    return response()->json([
      'message' => 'User retrieved successfully',
      'user' => $user
    ]);
  }
  public function me()
  {
      $user = auth()->user();
      return response()->json([
          'user' => $user
      ]);
  }

  public function deleteUser(Request $request): JsonResponse
  {
    $user = $request->user();
    if (!$user) {
      return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    try {
      $user->delete();
      return response()->json(['message' => 'User deleted successfully'], 200);
    } catch (\Exception $e) {
      Log::error('User deletion failed', ['error' => $e->getMessage()]);
      return response()->json([
        'message' => 'Failed to delete user',
        'error' => $e->getMessage()
      ], 500);
    }
  }
  
  public function updateFcmToken(Request $request)
  {
    $request->validate([
      'fcm_token' => 'required|string',
  ]);

  $user = $request->user();
  $user->fcm_token = $request->fcm_token;
  $user->save();

  return response()->json(['message' => 'FCMトークンを更新しました']);
  }

}
