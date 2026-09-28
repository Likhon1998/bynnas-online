<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'is_emi')) {
                $table->boolean('is_emi')->default(false)->after('is_baki');
            }
            if (! Schema::hasColumn('orders', 'emi_down_payment')) {
                $table->decimal('emi_down_payment', 12, 2)->default(0)->after('is_emi');
            }
            if (! Schema::hasColumn('orders', 'emi_months')) {
                $table->unsignedTinyInteger('emi_months')->nullable()->after('emi_down_payment');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'emi_balance')) {
                $table->decimal('emi_balance', 12, 2)->default(0)->after('baki_balance');
            }
        });

        if (! Schema::hasTable('customer_emi_plans')) {
            Schema::create('customer_emi_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('principal', 12, 2);
                $table->decimal('down_payment', 12, 2)->default(0);
                $table->unsignedTinyInteger('months');
                $table->decimal('installment_amount', 12, 2);
                $table->decimal('total_payable', 12, 2);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('remaining_amount', 12, 2);
                $table->string('status', 24)->default('active'); // active|completed|cancelled
                $table->date('started_at')->nullable();
                $table->timestamps();

                $table->index(['shop_id', 'customer_id']);
                $table->index(['shop_id', 'status']);
            });
        }

        if (! Schema::hasTable('customer_emi_installments')) {
            Schema::create('customer_emi_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
                $table->foreignId('emi_plan_id')->constrained('customer_emi_plans')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('sequence');
                $table->date('due_date');
                $table->decimal('amount', 12, 2);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->string('status', 24)->default('pending'); // pending|partial|paid|overdue
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['emi_plan_id', 'sequence']);
                $table->index(['shop_id', 'status', 'due_date']);
            });
        }

        if (! Schema::hasTable('customer_emi_entries')) {
            Schema::create('customer_emi_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('emi_plan_id')->nullable()->constrained('customer_emi_plans')->nullOnDelete();
                $table->foreignId('installment_id')->nullable()->constrained('customer_emi_installments')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 32); // plan|payment|adjustment|refund
                $table->decimal('amount', 12, 2); // + raises emi balance, - lowers
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
        Schema::dropIfExists('customer_emi_entries');
        Schema::dropIfExists('customer_emi_installments');
        Schema::dropIfExists('customer_emi_plans');

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'emi_balance')) {
                $table->dropColumn('emi_balance');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach (['emi_months', 'emi_down_payment', 'is_emi'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
