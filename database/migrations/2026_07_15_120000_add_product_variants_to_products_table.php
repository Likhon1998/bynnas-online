<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('variant_group')->nullable()->after('sku');
            $table->string('color')->nullable()->after('variant_group');
            $table->string('color_hex', 7)->nullable()->after('color');
            $table->string('storage')->nullable()->after('color_hex');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['variant_group', 'color', 'color_hex', 'storage']);
        });
    }
};
