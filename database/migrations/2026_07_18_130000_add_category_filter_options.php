<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->json('filter_options')->nullable()->after('product_count_label');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('availability', 40)->default('in_stock')->after('stock_quantity');
            $table->json('filter_attributes')->nullable()->after('availability');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('filter_options');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['availability', 'filter_attributes']);
        });
    }
};
