<?php

namespace Tests;

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /** Staff pages run on the "admin" guard, so auth tests need a real shop admin. */
    protected function shopAdmin(array $attributes = []): User
    {
        if (! Role::where('name', 'Admin')->exists()) {
            $this->seed(RolesAndPermissionsSeeder::class);
        }

        $shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $user = User::factory()->create($attributes);
        $user->forceFill(['shop_id' => $shop->id, 'role' => 'admin'])->save();
        $user->assignRole('Admin');

        return $user;
    }
}
