<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('return_product_id')->nullable()->constrained('products')->nullOnDelete()->after('exchange_for_order_id');
            $table->integer('return_qty')->nullable()->after('return_product_id');
            $table->decimal('exchange_credit', 10, 2)->nullable()->after('return_qty');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['return_product_id']);
            $table->dropColumn(['return_product_id', 'return_qty', 'exchange_credit']);
        });
    }
};