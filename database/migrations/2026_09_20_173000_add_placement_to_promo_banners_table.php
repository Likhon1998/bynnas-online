<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_banners', function (Blueprint $table) {
            if (! Schema::hasColumn('promo_banners', 'placement')) {
                $table->string('placement', 32)->default('deals')->after('theme');
                $table->index(['shop_id', 'placement', 'is_active']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('promo_banners', function (Blueprint $table) {
            if (Schema::hasColumn('promo_banners', 'placement')) {
                $table->dropIndex(['shop_id', 'placement', 'is_active']);
                $table->dropColumn('placement');
            }
        });
    }
};
