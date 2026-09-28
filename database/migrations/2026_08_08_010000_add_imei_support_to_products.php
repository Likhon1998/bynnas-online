<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'requires_imei')) {
                $table->boolean('requires_imei')->default(false)->after('ram');
            }
        });

        if (! Schema::hasTable('product_imeis')) {
            Schema::create('product_imeis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('imei', 32);
                $table->string('status', 20)->default('available'); // available|sold|reserved
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->unsignedBigInteger('order_item_id')->nullable();
                $table->timestamps();

                $table->unique('imei');
                $table->index(['product_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_imeis');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'requires_imei')) {
                $table->dropColumn('requires_imei');
            }
        });
    }
};
