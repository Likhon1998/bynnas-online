<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->json('items');
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('utm_source', 60)->nullable();
            $table->string('utm_medium', 60)->nullable();
            $table->string('utm_campaign', 120)->nullable();
            $table->string('landing_page', 255)->nullable();
            $table->string('status', 20)->default('active');
            $table->dateTime('last_activity_at');
            $table->dateTime('contacted_at')->nullable();
            $table->foreignId('contacted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('recovered_at')->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'token']);
            $table->index(['shop_id', 'status', 'last_activity_at']);
            $table->index(['shop_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
