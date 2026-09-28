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
    Schema::create('stock_movements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained(); // The user/cashier who made the change
        
        // 'in' (new stock), 'out' (damaged/returned), 'sale' (automated via POS)
        $table->string('type'); 
        
        $table->integer('quantity'); // The amount added or removed (e.g., 5 or -2)
        
        // Snapshot of the stock at the exact moment of change
        $table->integer('previous_stock');
        $table->integer('current_stock');
        
        // A reason for the change (e.g., "New Shipment from Supplier", "Expired")
        $table->string('reference')->nullable(); 
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
