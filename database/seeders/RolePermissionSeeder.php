<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        // Sesuaikan dengan guard yang kamu pakai (JWT biasanya 'api')
        $guard = config('auth.defaults.guard', 'api');

        // Modul & aksi yang tersedia di aplikasi
        $modules = [
            'project'     => ['view', 'create', 'update', 'delete'],
            'activity'    => ['view', 'create', 'update', 'delete'],
            'mitra'       => ['view', 'create', 'update', 'delete'],
            'bc'          => ['view', 'create', 'update', 'delete'],
            'certificate' => ['view', 'create', 'update', 'delete'],
            'finance'     => ['view', 'update'],
        ];

        $permissions = [];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}-{$action}";
                $permissions[] = $name;

                Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => $guard,
                ]);
            }
        }

        $permissions = array_values(array_unique($permissions));

        $adminPermissions = array_filter($permissions, function ($permission) {
            return ! str_ends_with($permission, '-delete');
        });

        /**
         * Mapping role → permission.
         *
         * super_admin punya semua permission.
         * admin: akses penuh kecuali aksi delete.
         * staff & user TIDAK dapat permission dari role,
         * supaya permission mereka bisa diatur fleksibel per user
         * via endpoint /auth/role (user-level permission).
         */
        $rolePermissions = [
            'super_admin' => $permissions,
            'admin'       => array_values($adminPermissions),
            'staff'       => [], // fleksibel per user
            'user'        => [], // fleksibel per user
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => $guard,
            ]);

            $role->syncPermissions($perms);
        }

        // OPTIONAL: jadikan user pertama sebagai super_admin
        $firstUser = User::query()->orderBy('id')->first();

        if ($firstUser && ! $firstUser->hasRole('super_admin')) {
            $firstUser->assignRole('super_admin');
        }
    }
}
