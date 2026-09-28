<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'process pos sales',
            'manage inventory',
            'view sales ledger',
            'manage staff',
            'manage roles',
            'manage counters',
            'manage website',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $cashierRole = Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']);
        $cashierRole->syncPermissions([
            'view dashboard',
            'process pos sales',
            'view sales ledger',
        ]);

        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $managerRole->syncPermissions([
            'view dashboard',
            'process pos sales',
            'manage inventory',
            'view sales ledger',
            'manage website',
        ]);

        $shopOwnerRole = Role::firstOrCreate(['name' => 'Shop Owner', 'guard_name' => 'web']);
        $shopOwnerRole->syncPermissions(Permission::all());

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
    }
}
