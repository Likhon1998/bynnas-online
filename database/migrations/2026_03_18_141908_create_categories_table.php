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
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        // Crucial: This links the category to the specific shop
        $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
        
        $table->string('name');
        $table->string('slug')->nullable(); 
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
