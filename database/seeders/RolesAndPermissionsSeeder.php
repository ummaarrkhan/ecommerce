<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'manage products',
            'manage categories',
            'manage inventory',
            'manage orders',
            'manage customers',
            'manage staff',
            'manage roles',
            'view reports',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate(
            'super-admin',
            'web'
        );

        $admin = Role::findOrCreate(
            'admin',
            'web'
        );

        Role::findOrCreate('customer', 'web');

        // Super-admin receives all defined permissions.
        $superAdmin->syncPermissions($permissions);

        // Admin receives operational permissions only.
        $admin->syncPermissions([
            'view dashboard',
            'manage products',
            'manage categories',
            'manage inventory',
            'manage orders',
            'manage customers',
            'view reports',
        ]);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}
