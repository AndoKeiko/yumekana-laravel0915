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
        Log::info('Login attempt', ['email' => $request->email]);

        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = \App\Models\User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Login failed: Invalid credentials', ['email' => $request->email]);
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            Log::info('User authenticated successfully', ['user_id' => $user->id]);

            $token = $user->createToken('auth-token')->plainTextToken;
            Log::info('Token created successfully');

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function logout(Request $request)
    {
        Log::info('Logout attempt', ['user_id' => $request->user()->id]);

        try {
            $request->user()->currentAccessToken()->delete();
            Log::info('User logged out successfully', ['user_id' => $request->user()->id]);

            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
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
        Log::info('User info request', ['user_id' => $request->user()->id]);

        try {
            return response()->json($request->user());
        } catch (\Exception $e) {
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
            $user = $request->user();
        
            if (!$user) {
                Log::warning('Token refresh attempt for unauthenticated user');
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
        
            // 古いトークンを無効化
            $user->tokens()->delete();
        
            $newAccessToken = $user->createToken('auth-token', ['*'], now()->addMinutes(15));
            $newRefreshToken = $user->createToken('refresh-token', ['*'], now()->addDays(7));
        
            Log::info('Tokens refreshed successfully', ['user_id' => $user->id]);

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
            throw $e;
        }
    }
}