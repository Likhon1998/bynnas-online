<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No ->after() so this stays portable on MySQL and PostgreSQL.
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'deals_kicker')) {
                $table->string('deals_kicker')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'deals_title')) {
                $table->string('deals_title')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'deals_title_accent')) {
                $table->string('deals_title_accent')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'deals_subtitle')) {
                $table->string('deals_subtitle', 500)->nullable();
            }
        });

        Schema::table('promo_banners', function (Blueprint $table) {
            if (! Schema::hasColumn('promo_banners', 'badge_text')) {
                $table->string('badge_text')->nullable();
            }
            if (! Schema::hasColumn('promo_banners', 'highlight_text')) {
                $table->string('highlight_text')->nullable();
            }
            if (! Schema::hasColumn('promo_banners', 'discount_badge')) {
                $table->string('discount_badge')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach (['deals_kicker', 'deals_title', 'deals_title_accent', 'deals_subtitle'] as $col) {
                if (Schema::hasColumn('site_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('promo_banners', function (Blueprint $table) {
            foreach (['badge_text', 'highlight_text', 'discount_badge'] as $col) {
                if (Schema::hasColumn('promo_banners', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
