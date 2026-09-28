<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('type'); // asset, liability, equity, income, expense
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_no');
            $table->string('type'); // sale, refund, transfer, petty_cash, opening, web_sale, web_settlement
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->timestamps();

            $table->unique(['shop_id', 'type', 'reference_type', 'reference_id'], 'acct_txn_unique_ref');
        });

        Schema::create('account_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('entry_type', ['debit', 'credit']);
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_entries');
        Schema::dropIfExists('account_transactions');
        Schema::dropIfExists('accounts');
    }
};
