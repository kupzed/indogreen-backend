<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request): UserResource|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $this->authService->register($validator->validated());

        return new UserResource($user);
    }

    /**
     * Handle user login.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        try {
            $token = $this->authService->login($credentials);
            return $this->respondWithToken($token);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(): UserResource
    {
        return new UserResource($this->authService->me());
    }

    /**
     * Handle user logout.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh the JWT token.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(Auth::refresh());
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): UserResource|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $this->authService->me();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = $this->authService->updateProfile($user, $validated);

        return new UserResource($user);
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/[a-z]/',  // minimal 1 huruf kecil
                'regex:/[A-Z]/',  // minimal 1 huruf besar
                'different:current_password',
            ],
        ], [
            'password.regex' => 'Password harus mengandung huruf kecil dan huruf besar.',
            'password.different' => 'Password baru tidak boleh sama dengan password lama.',
        ]);

        $user = $this->authService->me();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $this->authService->changePassword($user, $validated);
            return response()->json(['message' => 'Password berhasil diperbarui']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Format the response with the token.
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ]);
    }
}