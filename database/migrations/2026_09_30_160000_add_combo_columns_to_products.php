<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_combo')) {
                $table->boolean('is_combo')->default(false)->after('is_new_arrival');
            }
            if (! Schema::hasColumn('products', 'combo_items')) {
                $table->text('combo_items')->nullable()->after('is_combo');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['shop_id', 'is_combo'], 'products_shop_combo_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_shop_combo_index');
            $table->dropColumn(['is_combo', 'combo_items']);
        });
    }
};
