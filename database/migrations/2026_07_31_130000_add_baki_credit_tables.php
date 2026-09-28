<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'credit_amount')) {
                $table->decimal('credit_amount', 12, 2)->default(0)->after('discount_amount');
            }
            if (! Schema::hasColumn('orders', 'is_baki')) {
                $table->boolean('is_baki')->default(false)->after('credit_amount');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'baki_balance')) {
                $table->decimal('baki_balance', 12, 2)->default(0)->after('reward_points');
            }
        });

        if (! Schema::hasTable('customer_baki_entries')) {
            Schema::create('customer_baki_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 32); // sale|payment|adjustment|refund
                $table->decimal('amount', 12, 2); // + increases baki, - decreases
                $table->string('method', 40)->nullable();
                $table->string('note')->nullable();
                $table->timestamps();

                $table->index(['shop_id', 'customer_id']);
                $table->index(['customer_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_baki_entries');

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'baki_balance')) {
                $table->dropColumn('baki_balance');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'is_baki')) {
                $table->dropColumn('is_baki');
            }
            if (Schema::hasColumn('orders', 'credit_amount')) {
                $table->dropColumn('credit_amount');
            }
        });
    }
};
