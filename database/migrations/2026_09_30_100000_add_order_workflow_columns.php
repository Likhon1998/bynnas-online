<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('status');
                $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
                $table->string('verification_method', 30)->nullable()->after('verified_by');
                $table->string('verification_notes', 500)->nullable()->after('verification_method');
            }
            if (! Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('shipping_tracking_no');
                $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            }
            if (! Schema::hasColumn('orders', 'return_requested_at')) {
                $table->timestamp('return_requested_at')->nullable()->after('delivered_at');
                $table->string('return_reason', 500)->nullable()->after('return_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'verified_at', 'verification_method', 'verification_notes',
                'shipped_at', 'delivered_at', 'return_requested_at', 'return_reason',
            ]);
        });
    }
};
