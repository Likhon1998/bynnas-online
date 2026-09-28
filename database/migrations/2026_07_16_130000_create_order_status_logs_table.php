<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_courier')->nullable()->after('delivery_charge');
            $table->string('shipping_tracking_no')->nullable()->after('shipping_courier');
        });

        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('label');
            $table->text('note')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_courier', 'shipping_tracking_no']);
        });
    }
};
