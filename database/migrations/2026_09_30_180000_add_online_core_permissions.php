<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Online-core permissions that replace owner-only / retail-ledger checks.
     * Grant (never revoke) so custom role edits made in the admin are preserved.
     */
    private const GRANTS = [
        'manage orders' => ['Admin', 'Shop Owner', 'Manager'],
        'manage customers' => ['Admin', 'Shop Owner', 'Manager', 'Cashier'],
        'view reports' => ['Admin', 'Shop Owner'],
        'manage accounts' => ['Admin', 'Shop Owner'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::GRANTS as $name => $roles) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::GRANTS))->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
