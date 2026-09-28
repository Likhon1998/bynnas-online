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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Basic", "Pro"
            $table->decimal('price', 10, 2); // e.g., 500.00
            
            // NEW: Staff Limit (0 = Only owner, 999 = Unlimited)
            $table->integer('max_staff')->default(0); 
            
            $table->integer('duration_days'); // e.g., 30 for monthly, 365 for yearly
            $table->text('features')->nullable(); // JSON list of features
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};