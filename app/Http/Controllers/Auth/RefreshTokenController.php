<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefreshTokenController extends Controller
{
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // 古いトークンを無効化
        $user->tokens()->delete();
        
        // 新しいトークンを生成
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}