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

    public function users()
    {
        /** @var \App\Models\User|null $actor */
        $actor = Auth::user();

        if (! $actor) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $actor->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = User::query()
            ->where('id', '!=', $actor->id)
            ->orderBy('name');

        if ($actor->hasRole('admin') && ! $actor->hasRole('super_admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['admin', 'super_admin']);
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

        // Proteksi agar user tidak bisa menurunkan atau mengubah role-nya sendiri secara tidak sengaja
        if ($actor->id === $targetUser->id) {
            return response()->json([
                'message' => 'Kamu tidak boleh mengubah role milik akun kamu sendiri.',
            ], 403);
        }

        // Aturan Hierarki: Admin dilarang keras memodifikasi user setingkat atau memberikan role admin ke user lain
        if ($actor->hasRole('admin') && ! $actor->hasRole('super_admin')) {
            if ($targetUser->hasAnyRole(['admin', 'super_admin'])) {
                return response()->json([
                    'message' => 'Admin tidak boleh mengubah user dengan role admin atau super_admin.',
                ], 403);
            }
            if (in_array($data['role'], ['admin', 'super_admin'])) {
                return response()->json([
                    'message' => 'Admin tidak boleh memberikan role admin atau super_admin.',
                ], 403);
            }
        }

        $guard = config('auth.defaults.guard', 'api');

        $role = Role::firstOrCreate([
            'name'       => $data['role'],
            'guard_name' => $guard,
        ]);

        // Mengambil snapshot role & permission saat ini untuk kepentingan audit log (perbandingan data lama)
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

        // Mengambil snapshot setelah perubahan untuk mencatat perbedaan yang terjadi di audit log
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

    public function config()
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $allRoles = Role::pluck('name')->toArray();

        $filteredRoles = $allRoles;
        if ($actor->hasRole('admin') && ! $actor->hasRole('super_admin')) {
            $filteredRoles = array_values(array_filter($allRoles, function($role) {
                return !in_array($role, ['admin', 'super_admin']);
            }));
        }

        $rolesData = array_map(function($role) {
            return [
                'key'   => $role,
                'label' => ucwords(str_replace('_', ' ', $role))
            ];
        }, $filteredRoles);

        $modules = [
            ['key' => 'project',     'label' => 'Project'],
            ['key' => 'activity',    'label' => 'Activity'],
            ['key' => 'mitra',       'label' => 'Mitra'],
            ['key' => 'bc',          'label' => 'Barang Sertifikat'],
            ['key' => 'certificate', 'label' => 'Sertifikat'],
            ['key' => 'finance',     'label' => 'Finance', 'actions' => [
                ['key' => 'view',   'label' => 'View'],
                ['key' => 'update', 'label' => 'Update'],
            ]],
        ];

        $actions = [
            ['key' => 'view',   'label' => 'View'],
            ['key' => 'create', 'label' => 'Create'],
            ['key' => 'update', 'label' => 'Update'],
            ['key' => 'delete', 'label' => 'Delete'],
        ];

        return response()->json([
            'roles'   => $rolesData,
            'modules' => $modules,
            'actions' => $actions,
        ]);
    }
}
