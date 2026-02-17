<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('web')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::guard('web')->user();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user !== null && $user->currentAccessToken() !== null) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $current = $user?->currentAccessToken();
        if ($user === null || $current === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $newToken = $user->createToken('api')->plainTextToken;
        $current->delete();
        return response()->json([
            'token' => $newToken,
        ]);
    }

    public function revokeAllTokens(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        if ($actor === null || $actor->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $userClass = \App\Models\User::class;
        /** @var \App\Models\User|null $target */
        $target = $userClass::find($id);
        if ($target === null) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $target->tokens()->delete();
        return response()->json(['message' => 'All tokens revoked']);
    }
}
