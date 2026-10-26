<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Authenticate user credentials, check active status, and issue Sanctum token with 8-hour expiration.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $normalizedEmail = strtolower(trim($credentials['email']));
        $user = User::where('email', $normalizedEmail)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.'
            ], 401);
        }

        // Access Authorization Status Guard
        if (($user->status ?? 'Active') !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'Access Revoked: Your account is marked as Inactive in User Management.'
            ], 403);
        }

        // Issue token with explicit 8-hour expiration
        $expiresAt = now()->addHours(8);
        $token = $user->createToken('cams-token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'expires_at' => $expiresAt->toISOString(),
            'user' => $this->formatUserPayload($user),
        ]);
    }

    /**
     * Refresh active session token for 8 additional hours.
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $user->currentAccessToken()?->delete();

        $expiresAt = now()->addHours(8);
        $newToken = $user->createToken('cams-token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Session token refreshed successfully.',
            'token' => $newToken,
            'expires_at' => $expiresAt->toISOString()
        ]);
    }

    /**
     * Register a new user account and issue initial access token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'Cadet',
            'department' => $validated['department'] ?? 'ICT',
            'status' => 'Active',
            'password_changed_at' => now(),
        ]);

        $expiresAt = now()->addHours(8);
        $token = $user->createToken('cams-token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'token' => $token,
            'expires_at' => $expiresAt->toISOString(),
            'user' => $this->formatUserPayload($user),
        ], 201);
    }

    /**
     * Revoke active access token for current authenticated session.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.'
        ]);
    }

    /**
     * Retrieve payload details for currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $this->formatUserPayload($request->user())
        ]);
    }

    /**
     * Format uniform user response payload matching CAMS Vue store specifications.
     */
    private function formatUserPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'Cadet',
            'department' => $user->department ?? 'ICT',
            'status' => $user->status ?? 'Active',
        ];
    }
}