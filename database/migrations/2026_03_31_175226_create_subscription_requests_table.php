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
    Schema::create('subscription_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
        $table->foreignId('package_id')->constrained()->cascadeOnDelete();
        $table->string('payment_method'); // e.g., "bKash", "Bank Transfer"
        $table->string('transaction_id')->nullable(); 
        $table->string('document_path'); // Where the uploaded screenshot is saved
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->text('admin_notes')->nullable(); // If HQ rejects it, they can leave a reason
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_requests');
    }
};
