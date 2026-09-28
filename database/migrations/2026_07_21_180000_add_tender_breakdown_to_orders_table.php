<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('cash_paid', 12, 2)->nullable()->after('paid_amount');
            $table->decimal('card_paid', 12, 2)->nullable()->after('cash_paid');
            $table->decimal('mobile_paid', 12, 2)->nullable()->after('card_paid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cash_paid', 'card_paid', 'mobile_paid']);
        });
    }
};
