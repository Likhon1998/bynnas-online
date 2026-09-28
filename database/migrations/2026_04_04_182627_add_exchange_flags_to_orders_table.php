<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Flags this order as a product exchange, meaning no future refunds allowed
            $table->boolean('is_exchange_receipt')->default(false)->after('status');
            
            // Links this new receipt back to the original order they returned
            $table->foreignId('exchange_for_order_id')->nullable()->constrained('orders')->nullOnDelete()->after('is_exchange_receipt');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['exchange_for_order_id']);
            $table->dropColumn(['is_exchange_receipt', 'exchange_for_order_id']);
        });
    }
};