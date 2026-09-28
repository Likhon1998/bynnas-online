<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('pos_discount_type', 20)->nullable()->after('sale_ends_at');
            $table->decimal('pos_discount_value', 12, 2)->nullable()->after('pos_discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pos_discount_type', 'pos_discount_value']);
        });
    }
};
