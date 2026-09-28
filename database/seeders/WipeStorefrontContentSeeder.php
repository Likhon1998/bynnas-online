<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deletes all storefront/demo catalog + CMS rows.
 * Keeps users, shops, roles, and site_settings skeleton.
 */
class WipeStorefrontContentSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'order_status_logs',
            'order_items',
            'orders',
            'exchanges',
            'stock_transfer_items',
            'stock_transfers',
            'stock_movements',
            'warehouse_stocks',
            'product_imeis',
            'product_images',
            'products',
            'brands',
            'categories',
            'purchase_return_items',
            'purchase_returns',
            'purchase_order_items',
            'purchase_orders',
            'suppliers',
            'hero_slides',
            'promo_banners',
            'site_features',
            'navigation_links',
            'cms_reviews',
            'cms_blogs',
            'cms_blog_categories',
            'cms_faqs',
            'cms_faq_categories',
            'cms_pages',
            'cms_contact_messages',
            'cms_newsletter_subscribers',
            'customer_emi_installments',
            'customer_emi_entries',
            'customer_emi_plans',
            'customer_baki_entries',
            'customers',
            'courier_services',
            'counter_sessions',
            'counters',
            'stock_locations',
            'account_entries',
            'account_transactions',
            'accounts',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        // Keep only the production admin account.
        if (Schema::hasTable('users')) {
            DB::table('model_has_roles')->whereNotIn(
                'model_id',
                DB::table('users')->where('email', env('ADMIN_EMAIL', 'admin@maksgadget.com'))->pluck('id')
            )->delete();

            DB::table('users')
                ->where('email', '!=', env('ADMIN_EMAIL', 'admin@maksgadget.com'))
                ->delete();
        }

        Schema::enableForeignKeyConstraints();

        $this->command?->info('Storefront/demo content wiped. Re-run ProductionSeeder for admin bootstrap.');
    }
}
