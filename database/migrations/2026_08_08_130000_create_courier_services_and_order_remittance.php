<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shop_id', 'is_active', 'sort_order']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('courier_service_id')->nullable()->after('shipping_courier')->constrained('courier_services')->nullOnDelete();
            $table->timestamp('courier_collected_at')->nullable()->after('shipping_tracking_no');
            $table->decimal('courier_collected_amount', 12, 2)->nullable()->after('courier_collected_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('courier_service_id');
            $table->dropColumn(['courier_collected_at', 'courier_collected_amount']);
        });

        Schema::dropIfExists('courier_services');
    }
};
