<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'delivery_inside_dhaka')) {
                $table->decimal('delivery_inside_dhaka', 10, 2)->default(60)->after('social_links');
            }
            if (! Schema::hasColumn('site_settings', 'delivery_outside_dhaka')) {
                $table->decimal('delivery_outside_dhaka', 10, 2)->default(120)->after('delivery_inside_dhaka');
            }
            if (! Schema::hasColumn('site_settings', 'delivery_free_enabled')) {
                $table->boolean('delivery_free_enabled')->default(true)->after('delivery_outside_dhaka');
            }
            if (! Schema::hasColumn('site_settings', 'delivery_free_min_amount')) {
                $table->decimal('delivery_free_min_amount', 12, 2)->default(10000)->after('delivery_free_enabled');
            }
            if (! Schema::hasColumn('site_settings', 'delivery_cod_enabled')) {
                $table->boolean('delivery_cod_enabled')->default(true)->after('delivery_free_min_amount');
            }
            if (! Schema::hasColumn('site_settings', 'delivery_confirmation_enabled')) {
                $table->boolean('delivery_confirmation_enabled')->default(false)->after('delivery_cod_enabled');
            }
            if (! Schema::hasColumn('site_settings', 'delivery_confirmation_amount')) {
                $table->decimal('delivery_confirmation_amount', 10, 2)->default(0)->after('delivery_confirmation_enabled');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'delivery_zone')) {
                $table->string('delivery_zone', 32)->nullable()->after('delivery_charge');
            }
            if (! Schema::hasColumn('orders', 'confirmation_charge')) {
                $table->decimal('confirmation_charge', 10, 2)->default(0)->after('delivery_zone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach ([
                'delivery_inside_dhaka',
                'delivery_outside_dhaka',
                'delivery_free_enabled',
                'delivery_free_min_amount',
                'delivery_cod_enabled',
                'delivery_confirmation_enabled',
                'delivery_confirmation_amount',
            ] as $col) {
                if (Schema::hasColumn('site_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach (['delivery_zone', 'confirmation_charge'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
