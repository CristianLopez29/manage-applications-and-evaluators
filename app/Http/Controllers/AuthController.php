<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login and obtain API token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *             @OA\Property(property="password", type="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="2|abcdef..."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Test User"),
     *                 @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *                 @OA\Property(property="role", type="string", example="admin")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Logout current session (revoke current token)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Logged out")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/refresh-token",
     *     tags={"Auth"},
     *     summary="Refresh API token (rotate current token)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="New token issued",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="3|ghijkl...")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/users/{id}/tokens/revoke-all",
     *     tags={"Auth"},
     *     summary="Revoke all tokens for a user (admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="All tokens revoked"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function revokeAllTokens(Request $request, string $id): JsonResponse
    {
        $userId = (int) $id;
        if ($userId <= 0) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $actor = $request->user();
        if ($actor === null || $actor->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $userClass = \App\Models\User::class;
        /** @var \App\Models\User|null $target */
        $target = $userClass::find($userId);
        if ($target === null) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $target->tokens()->delete();
        return response()->json(['message' => 'All tokens revoked']);
    }
}
