<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Link to the Shop and the Cashier
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); 
            
            // FIX: Removed ->constrained() so it doesn't crash if the counters table isn't created yet.
            $table->unsignedBigInteger('counter_id')->nullable();
            
            // Link to the Customer
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('invoice_no')->unique(); // e.g., INV-0001
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2);
            $table->decimal('change_amount', 10, 2)->default(0);
            $table->string('payment_method')->default('cash'); // cash, card, bkash
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};