<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Services\ActivityLogService;

class RoleController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Role & permissions user yang sedang login.
     * GET /auth/role/me
     */
    public function me()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->getRoleNames(),                      // ["super_admin"]
            'permissions' => $user->getAllPermissions()->pluck('name'),  // ["project-view", "project-create", "activity-view", ...]
        ]);
    }

    /**
     * Daftar semua user + roles & permissions.
     * GET /auth/role/users
     *
     * - super_admin: bisa melihat semua user (termasuk super_admin lain)
     * - admin: tidak boleh melihat user yang punya role super_admin
     */
    public function users()
    {
        /** @var \App\Models\User|null $actor */
        $actor = Auth::user();

        if (! $actor) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Safety: pastikan hanya super_admin atau admin
        if (! $actor->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = User::query()
            ->where('id', '!=', $actor->id)   // ⬅️ JANGAN kirim user yang sedang login
            ->orderBy('name');

        // Kalau cuma admin (bukan super_admin), jangan tampilkan user super_admin
        if ($actor->hasRole('admin') && ! $actor->hasRole('super_admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            });
        }

        $users = $query->get();

        $data = $users->map(function (User $user) {
            return [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'roles'       => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ];
        })->values();

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Update role & job (permissions) user tertentu.
     * PUT /auth/role
     *
     * - super_admin: bebas mengubah siapa saja
     * - admin:
     *      - tidak boleh mengubah user yang punya role super_admin
     *      - tidak boleh memberikan role super_admin ke siapa pun
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User|null $actor */
        $actor = Auth::user();

        if (! $actor) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $actor->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'user_id'       => ['required', 'integer', 'exists:users,id'],
            'role'          => ['required', 'string'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['boolean'],
        ]);

        /** @var \App\Models\User $targetUser */
        $targetUser = User::findOrFail($data['user_id']);

        // ⬇️ TIDAK boleh edit role milik diri sendiri
        if ($actor->id === $targetUser->id) {
            return response()->json([
                'message' => 'Kamu tidak boleh mengubah role milik akun kamu sendiri.',
            ], 403);
        }

        // kalau hanya admin (bukan super_admin), batasi sentuh super_admin
        if ($actor->hasRole('admin') && ! $actor->hasRole('super_admin')) {
            if ($targetUser->hasRole('super_admin')) {
                return response()->json([
                    'message' => 'Admin tidak boleh mengubah user dengan role super_admin.',
                ], 403);
            }
            if ($data['role'] === 'super_admin') {
                return response()->json([
                    'message' => 'Admin tidak boleh memberikan role super_admin.',
                ], 403);
            }
        }

        $guard = config('auth.defaults.guard', 'api');

        $role = Role::firstOrCreate([
            'name'       => $data['role'],
            'guard_name' => $guard,
        ]);

        $previousSnapshot = [
            'roles' => $targetUser->getRoleNames()->toArray(),
            'permissions' => $targetUser->getAllPermissions()->pluck('name')->toArray(),
        ];

        $targetUser->syncRoles([$role->name]);

        $permissionNames = [];
        if (! empty($data['permissions'])) {
            foreach ($data['permissions'] as $key => $value) {
                if ($value) {
                    $permissionNames[] = $key;
                }
            }
        }

        if (! empty($permissionNames)) {
            foreach ($permissionNames as $permName) {
                Permission::firstOrCreate([
                    'name'       => $permName,
                    'guard_name' => $guard,
                ]);
            }
            $targetUser->syncPermissions($permissionNames);
        } else {
            $targetUser->syncPermissions([]);
        }

        $currentSnapshot = [
            'roles' => $targetUser->getRoleNames()->toArray(),
            'permissions' => $targetUser->getAllPermissions()->pluck('name')->toArray(),
        ];

        $this->activityLogService->log(
            'role_assignment',
            User::class,
            $targetUser->id,
            $targetUser->name,
            sprintf('Role & permission user diperbarui oleh %s', $actor->name),
            $previousSnapshot,
            $currentSnapshot
        );

        return response()->json([
            'message' => 'User role & permissions updated successfully',
            'user'    => [
                'id'          => $targetUser->id,
                'name'        => $targetUser->name,
                'email'       => $targetUser->email,
                'roles'       => $targetUser->getRoleNames(),
                'permissions' => $targetUser->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /**
     * Mengambil konfigurasi module & permission actions untuk frontend.
     * GET /auth/role/config
     */
    public function config()
    {
        // Sesuaikan label ini dengan yang sebelumnya ada di frontend Svelte
        $modules = [
            ['key' => 'project',     'label' => 'Project'],
            ['key' => 'activity',    'label' => 'Activity'],
            ['key' => 'mitra',       'label' => 'Mitra'],
            ['key' => 'bc',          'label' => 'Barang Sertifikat'],
            ['key' => 'certificate', 'label' => 'Sertifikat'],
            ['key' => 'finance',     'label' => 'Finance'],
        ];

        $actions = [
            ['key' => 'view',   'label' => 'View'],
            ['key' => 'create', 'label' => 'Create'],
            ['key' => 'update', 'label' => 'Update'],
            ['key' => 'delete', 'label' => 'Delete'],
        ];

        return response()->json([
            'modules' => $modules,
            'actions' => $actions,
        ]);
    }
}
