<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Production bootstrap only: roles + shop + admin.
 * No products, blogs, heroes, reviews, features, or other demo CMS content.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $shop = Shop::query()->orderBy('id')->first() ?? Shop::create([
            'name' => env('SHOP_NAME', 'Bynnas Social'),
            'email' => AdminUserSeeder::EMAIL,
            'phone' => null,
            'address' => null,
            'is_active' => true,
        ]);

        $this->call(AdminUserSeeder::class);

        // Keep one settings row so the storefront does not crash; leave marketing fields null.
        $settings = SiteSetting::query()->first() ?? new SiteSetting;
        $settings->fill([
            'default_shop_id' => $shop->id,
            'store_name' => env('SHOP_NAME', 'Bynnas Social'),
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'special_offer_text' => null,
            'trusted_by_text' => null,
            'deals_kicker' => null,
            'deals_title' => null,
            'deals_title_accent' => null,
            'deals_subtitle' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'contact_address' => null,
            'contact_hours_weekday' => null,
            'contact_hours_weekend' => null,
            'social_links' => null,
            'delivery_inside_dhaka' => 60,
            'delivery_outside_dhaka' => 120,
            'delivery_free_enabled' => true,
            'delivery_free_min_amount' => 10000,
            'delivery_cod_enabled' => true,
            'delivery_confirmation_enabled' => false,
            'delivery_confirmation_amount' => 0,
        ])->save();

        // Homepage mid-promo strip (3 continuous banners) — editable in CMS → Landing Page
        $this->call(MidPromoBannerSeeder::class);

        $this->command?->info('Production ready: admin + mid promo banners (CMS editable).');
    }
}
