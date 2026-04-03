<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Handle user login.
     */
    public function login(array $credentials): string
    {
        if (! $token = Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $this->logActivity('login', 'User logged in successfully');

        return $token;
    }

    /**
     * Handle user logout.
     */
    public function logout(): void
    {
        $this->logActivity('logout', 'User logged out');
        Auth::logout();
    }

    /**
     * Get authenticated user.
     */
    public function me(): ?User
    {
        return Auth::user();
    }

    /**
     * Update user profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, array $data): void
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak cocok.'],
            ]);
        }

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password baru tidak boleh sama dengan password lama.'],
            ]);
        }

        $user->password = $data['password'];
        $user->save();

        $this->logActivity('password_update', 'User changed password');
    }

    /**
     * Register a new user.
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return $user;
    }

    /**
     * Log activity helper.
     */
    protected function logActivity(string $action, ?string $description = null): void
    {
        $this->activityLogService->log(
            $action,
            User::class,
            Auth::id(),
            Auth::user() ? Auth::user()->name : 'Unknown User',
            $description
        );
    }
}
