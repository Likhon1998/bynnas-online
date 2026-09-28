<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('site_settings', 'favicon_path')) {
            return;
        }

        // Postgres pooler can abort the whole transaction if hasColumn/alter race;
        // use IF NOT EXISTS when available.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE site_settings ADD COLUMN IF NOT EXISTS favicon_path varchar(255) null');

            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('favicon_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('site_settings', 'favicon_path')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('favicon_path');
        });
    }
};
