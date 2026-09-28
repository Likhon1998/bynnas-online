<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('shop_id')->constrained()->nullOnDelete();
            $table->unique(['shop_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
