<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{

    public function register() {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();

        return response()->json($user, 201);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Email atau password salah'], 401);
        }

        $this->logActivity('login', 'User logged in successfully');

        return $this->respondWithToken($token);
    }

    public function me()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function logout()
    {
        $this->logActivity('logout', 'User logged out');
        
        Auth::logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }

    protected function logActivity($action, $description = null)
    {
        $service = new ActivityLogService();
        $service->log(
            $action,
            User::class,
            Auth::id(),
            Auth::user() ? Auth::user()->name : 'Unknown User',
            $description
        );
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $userModel = User::find($user->id);
        if (!$userModel) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $userModel->name = $validated['name'];
        $userModel->save();

        return response()->json($userModel);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/[a-z]/',  // huruf kecil
                'regex:/[A-Z]/',  // huruf besar
                'different:current_password',
            ],
        ], [
            'password.regex' => 'Password harus mengandung huruf kecil dan huruf besar.',
            'password.different' => 'Password baru tidak boleh sama dengan password lama.',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Password lama tidak cocok'], 422);
        }

        if (Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Password baru tidak boleh sama dengan password lama'], 422);
        }

        // Model User punya cast 'password' => 'hashed', jadi set plain akan otomatis di-hash.
        $user->password = $validated['password'];
        $user->save();

        $this->logActivity('password_update', 'User changed password');

        return response()->json(['message' => 'Password berhasil diperbarui']);
    }

}