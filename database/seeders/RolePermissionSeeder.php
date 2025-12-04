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
        $modules = ['project', 'activity', 'mitra', 'bc', 'certificate'];
        $actions = ['view', 'create', 'update', 'delete'];

        $permissions = [];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $name = "{$module}-{$action}";
                $permissions[] = $name;

                Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => $guard,
                ]);
            }
        }

        /**
         * Mapping role → permission.
         *
         * super_admin & admin punya semua permission.
         * staff & user TIDAK dapat permission dari role,
         * supaya permission mereka bisa diatur fleksibel per user
         * via endpoint /auth/role (user-level permission).
         */
        $rolePermissions = [
            'super_admin' => $permissions,
            'admin'       => $permissions,
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
