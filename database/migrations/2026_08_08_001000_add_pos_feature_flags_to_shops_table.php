<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('pos_emi_enabled')->default(true)->after('is_active');
            $table->boolean('pos_baki_enabled')->default(true)->after('pos_emi_enabled');
            $table->boolean('pos_sale_enabled')->default(true)->after('pos_baki_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['pos_emi_enabled', 'pos_baki_enabled', 'pos_sale_enabled']);
        });
    }
};
