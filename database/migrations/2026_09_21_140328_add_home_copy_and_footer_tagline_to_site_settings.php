<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'footer_tagline')) {
                $table->string('footer_tagline')->nullable()->after('trusted_by_text');
            }
            if (! Schema::hasColumn('site_settings', 'home_copy')) {
                $table->json('home_copy')->nullable()->after('footer_tagline');
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (Schema::hasColumn('site_settings', 'home_copy')) {
                $table->dropColumn('home_copy');
            }
            if (Schema::hasColumn('site_settings', 'footer_tagline')) {
                $table->dropColumn('footer_tagline');
            }
        });
    }
};
