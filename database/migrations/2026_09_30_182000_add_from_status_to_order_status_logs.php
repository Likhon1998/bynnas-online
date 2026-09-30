<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('order_status_logs', 'from_status')) {
            Schema::table('order_status_logs', function (Blueprint $table) {
                $table->string('from_status', 40)->nullable()->after('order_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_status_logs', 'from_status')) {
            Schema::table('order_status_logs', function (Blueprint $table) {
                $table->dropColumn('from_status');
            });
        }
    }
};
