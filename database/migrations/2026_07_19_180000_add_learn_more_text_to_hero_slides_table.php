<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            if (! Schema::hasColumn('hero_slides', 'learn_more_text')) {
                $table->string('learn_more_text')->nullable()->after('button_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            if (Schema::hasColumn('hero_slides', 'learn_more_text')) {
                $table->dropColumn('learn_more_text');
            }
        });
    }
};
