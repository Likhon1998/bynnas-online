<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete(); 
            
            // 🚀 ACCOUNTABILITY TRACKING
            $table->foreignId('user_id')->constrained(); // WHO did it
            $table->foreignId('counter_id')->nullable()->constrained()->nullOnDelete(); // WHERE it happened
            
            // Product In (Returned)
            $table->foreignId('return_product_id')->constrained('products');
            $table->integer('return_qty');
            $table->decimal('return_value', 10, 2);
            
            // Product Out (Given)
            $table->foreignId('new_product_id')->constrained('products');
            $table->integer('new_qty');
            $table->decimal('new_value', 10, 2);
            
            // Money
            $table->decimal('price_difference', 10, 2); 
            $table->string('payment_method')->nullable(); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('exchanges');
    }
};