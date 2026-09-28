<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('offline_client_uuid')->nullable()->after('invoice_no');
            $table->unique(['shop_id', 'offline_client_uuid'], 'orders_shop_offline_client_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_shop_offline_client_uuid_unique');
            $table->dropColumn('offline_client_uuid');
        });
    }
};
