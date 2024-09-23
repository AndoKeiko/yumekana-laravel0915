<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;

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

            $accessToken = $user->createToken('auth-token', ['*'])->plainTextToken;
            $refreshToken = $user->createToken('refresh-token', ['*'])->plainTextToken;

            $domain = parse_url(config('app.url'), PHP_URL_HOST);
            $accessTokenCookie = Cookie::make('access_token', $accessToken, 15, '/', $domain, true, true, false, 'strict');
            $refreshTokenCookie = Cookie::make('refresh_token', $refreshToken, 10080, '/', $domain, true, true, false, 'strict');

            Log::info('Tokens created successfully', [
                'user_id' => $user->id,
                'access_token' => substr($accessToken, 0, 10),
                'refresh_token' => substr($refreshToken, 0, 10)
            ]);

            return response()->json([
                'user' => $user,
                'message' => 'Login successful'
            ])->withCookie($accessTokenCookie)
              ->withCookie($refreshTokenCookie);
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
        $request->user()->currentAccessToken()->delete();

        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        $accessTokenCookie = Cookie::forget('access_token');
        $refreshTokenCookie = Cookie::forget('refresh_token');
        $accessTokenCookie->setDomain($domain);
        $refreshTokenCookie->setDomain($domain);

        return response()->json(['message' => 'Logged out successfully'])
            ->withCookie($accessTokenCookie)
            ->withCookie($refreshTokenCookie);
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
            return response()->json(['error' => 'Failed to fetch user info', 'message' => $e->getMessage()], 500);
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

            $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::findToken($refreshToken);

            if (!$tokenRecord || !$tokenRecord->tokenable || ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast())) {
                Log::warning('Invalid or expired refresh token', ['refresh_token' => $refreshToken]);
                return response()->json(['error' => 'Invalid or expired refresh token'], 401);
            }

            $user = $tokenRecord->tokenable;
            $user->tokens()->delete();

            $newAccessToken = $user->createToken('auth-token', ['*']);
            $newAccessToken->token->expires_at = now()->addMinutes(15);
            $newAccessToken->token->save();

            $newRefreshToken = $user->createToken('refresh-token', ['*']);
            $newRefreshToken->token->expires_at = now()->addDays(7);
            $newRefreshToken->token->save();

            $domain = parse_url(config('app.url'), PHP_URL_HOST);
            $accessTokenCookie = Cookie::make('access_token', $newAccessToken->plainTextToken, 15, '/', $domain, true, true, false, 'strict');
            $refreshTokenCookie = Cookie::make('refresh_token', $newRefreshToken->plainTextToken, 10080, '/', $domain, true, true, false, 'strict');

            return response()->json(['message' => 'Token refreshed successfully'])
                ->withCookie($accessTokenCookie)
                ->withCookie($refreshTokenCookie);
        } catch (\Exception $e) {
            Log::error('Token refresh error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to refresh token', 'message' => $e->getMessage()], 500);
        }
    }
}