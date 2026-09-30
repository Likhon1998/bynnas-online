<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Ensures the admin panel login exists on the primary shop.
 * Safe to re-run: updates the same account instead of duplicating it.
 */
class AdminUserSeeder extends Seeder
{
    public const EMAIL = 'admin@bynnasonline.com';

    public const PASSWORD = '12345678';

    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first() ?? Shop::create([
            'name' => env('SHOP_NAME', 'Bynnas Social'),
            'email' => self::EMAIL,
            'is_active' => true,
        ]);

        $admin = User::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'shop_id' => $shop->id,
                'role' => 'admin',
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['Admin']);

        $this->command?->info('Admin login: '.self::EMAIL);
    }
}
